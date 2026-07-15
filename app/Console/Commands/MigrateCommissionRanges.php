<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Enterprise;
use App\Http\Models\Marketer;
use App\Http\Models\User;
use App\Helpers\UserHelper;

class MigrateCommissionRanges extends Command
{
    protected $signature = 'migrate:commission-ranges';
    protected $description = 'Migrates consumptionBasic/consumptionBreakdown to commissions, and updates user commission ranges';

    private int $marketersProcessed = 0;
    private array $unprocessedMarketers = [];
    private int $feesTransformed = 0;
    private int $usersProcessed = 0;
    private int $errors = 0;

    public function handle(): void
    {
        $enterprises = Enterprise::whereNotNull('commissionRanges')
            ->where('commissionRanges', '!=', [])
            ->get();

        $this->info("Found {$enterprises->count()} enterprises to process.");

        foreach ($enterprises as $enterprise) {
            $this->processEnterprise($enterprise);
        }

        $this->newLine();
        $this->info("Migration complete.");
        $this->table(
            ['Marketers processed', 'Fees transformed', 'Users processed', 'Errors'],
            [[$this->marketersProcessed, $this->feesTransformed, $this->usersProcessed, $this->errors]]
        );

        $this->info(json_encode($this->unprocessedMarketers));
    }

    private function processEnterprise(Enterprise $enterprise): void
    {
        $rangeMap = $this->buildRangeMap($enterprise);

        if (empty($rangeMap)) {
            $this->warn("Enterprise {$enterprise->_id} has no valid commission ranges, skipping.");
            return;
        }

        $marketers = Marketer::where('createdBy', $enterprise->getAttributes()['subdomainUser'])->get();

        $this->info("Enterprise {$enterprise->name}: {$marketers->count()} marketers, ranges: " . implode(', ', array_map(
                fn($v, $id) => "com{$v}→{$id}",
                array_keys($rangeMap),
                array_values($rangeMap)
            )));

        foreach ($marketers as $marketer) {
            $this->processMarketer($marketer, $rangeMap);
        }

        $this->processUsers($enterprise, $rangeMap);
    }

    /**
     * Updates all users of an enterprise, replacing {type: range, value: N}
     * with {type: range, rangeId: uuid} in their commissions map.
     */
    private function processUsers(Enterprise $enterprise, array $rangeMap): void
    {
        $hierarchyUsers = UserHelper::hierarchy($enterprise->getAttributes()['subdomainUser']);

        if (empty($hierarchyUsers)) {
            return;
        }

        $userIds = array_map(fn($user) => (string) $user['_id'], $hierarchyUsers);

        $users = User::whereIn('_id', $userIds)->get();

        foreach ($users as $user) {
            try {
                $commissions = $user->commissions ?? [];

                if (empty($commissions)) {
                    continue;
                }

                $changed = false;

                foreach ($commissions as $marketerId => $commission) {
                    if (
                        ($commission['type'] ?? null) !== 'range' ||
                        !isset($commission['value'])
                    ) {
                        continue;
                    }

                    $value = (int) $commission['value'];

                    if (!isset($rangeMap[$value])) {
                        $this->warn("User {$user->_id}: no range found for value {$value} in marketer {$marketerId}, skipping.");
                        continue;
                    }

                    $commissions[$marketerId] = [
                        'type'    => 'range',
                        'value' => $rangeMap[$value],
                    ];

                    $changed = true;
                }

                if ($changed) {
                    $user->commissions = $commissions;
                    $user->save();
                    $this->usersProcessed++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing user {$user->_id}: {$e->getMessage()}");
                $this->errors++;
            }
        }
    }

    /**
     * Builds a map from value (1, 2, 3) to range _id string.
     * comAs is excluded — it becomes the "base" field.
     */
    private function buildRangeMap(Enterprise $enterprise): array
    {
        $ranges = $enterprise->commissionRanges ?? [];
        $map = [];
        $changed = false;

        foreach ($ranges as $index => $range) {
            if (!isset($range['value'])) {
                continue;
            }

            if (empty($range['_id'])) {
                $ranges[$index]['_id'] = (string) new \MongoDB\BSON\ObjectId();
                $changed = true;
            }

            $map[(int) $range['value']] = (string) $ranges[$index]['_id'];

            if ($changed && isset($ranges[$index]['value'])) {
                unset($ranges[$index]['value']);
            }
        }

        if ($changed) {
            $enterprise->commissionRanges = $ranges;
            $enterprise->save();
        }

        return $map;
    }

    private function processMarketer(Marketer $marketer, array $rangeMap): void
    {
        try {
            $products = $marketer->products ?? [];
            $changed = false;

            foreach ($products as $energyType => $productList) {
                if (!is_array($productList)) {
                    continue;
                }

                if ($energyType === 'alarm') {
                    foreach ($productList as $productIndex => $product) {
                        if (!isset($product['consumptionBasic'])) {
                            continue;
                        }

                        $newCommissions = [$this->transformBasicToTranche($product['consumptionBasic'], $rangeMap)];

                        unset($products['alarm'][$productIndex]['consumptionBasic']);
                        $products['alarm'][$productIndex]['commissions'] = $newCommissions;
                        $products['alarm'][$productIndex]['commissionType'] = 'f';
                        $changed = true;
                    }
                    continue;
                }

                foreach ($productList as $productIndex => $product) {
                    if (!isset($product['fees']) || !is_array($product['fees'])) {
                        continue;
                    }

                    if ($energyType === 'dual') {
                        foreach ($product['fees'] as $feeIndex => $fee) {
                            foreach (['electricity', 'gas'] as $subType) {
                                if (!isset($fee[$subType])) {
                                    continue;
                                }

                                $hasBasic = isset($fee[$subType]['consumptionBasic']);
                                $newCommissions = $this->transformFee($fee[$subType], $rangeMap);

                                if ($newCommissions !== null) {
                                    unset(
                                        $products['dual'][$productIndex]['fees'][$feeIndex][$subType]['consumptionBasic'],
                                        $products['dual'][$productIndex]['fees'][$feeIndex][$subType]['consumptionBreakdown']
                                    );

                                    $products['dual'][$productIndex]['fees'][$feeIndex][$subType]['commissions'] = $newCommissions;
                                    $products['dual'][$productIndex]['fees'][$feeIndex][$subType]['commissionType'] = $hasBasic ? 'f' : $fee[$subType]['commissionType'];
                                    $this->feesTransformed++;
                                    $changed = true;
                                }
                            }
                        }

                        $products['dual'][$productIndex]['fees'] = array_values(
                            $products['dual'][$productIndex]['fees']
                        );
                        continue;
                    }

                    $productCommissionType = $product['commissionType'] ?? null;

                    foreach ($product['fees'] as $feeIndex => $fee) {
                        $hasBasic = isset($fee['consumptionBasic']);
                        $hasBreakdown = isset($fee['consumptionBreakdown']) && is_array($fee['consumptionBreakdown']);
                        $usedBasic = $hasBasic && !$hasBreakdown;
                        $newCommissions = $this->transformFee($fee, $rangeMap);

                        if ($newCommissions !== null) {
                            unset(
                                $products[$energyType][$productIndex]['fees'][$feeIndex]['consumptionBasic'],
                                $products[$energyType][$productIndex]['fees'][$feeIndex]['consumptionBreakdown']
                            );

                            $products[$energyType][$productIndex]['fees'][$feeIndex]['commissions'] = $newCommissions;
                            $products[$energyType][$productIndex]['fees'][$feeIndex]['commissionType'] = $usedBasic ? 'f' : $productCommissionType;
                            $this->feesTransformed++;
                            $changed = true;
                        }
                    }

                    // Remove commissionType from product and re-index fees
                    unset($products[$energyType][$productIndex]['commissionType']);
                    $products[$energyType][$productIndex]['fees'] = array_values(
                        $products[$energyType][$productIndex]['fees']
                    );
                    $changed = true;
                }
            }

            if ($changed) {
                $marketer->products = $products;
                $marketer->save();
                $this->marketersProcessed++;
            } else {
                $this->unprocessedMarketers[] = [
                    'id'   => (string) $marketer->_id,
                    'name' => $marketer->name,
                    'createdBy' => $marketer->createdBy
                ];
            }
        } catch (\Exception $e) {
            $this->error("Error processing marketer {$marketer->_id}: {$e->getMessage()}");
            $this->errors++;
        }
    }

    /**
     * Transforms a fee's consumption data into the new commissions format.
     * Returns null if the fee has no consumption data to transform.
     */
    private function transformFee(array $fee, array $rangeMap): ?array
    {

        if (isset($fee['consumptionBreakdown']) && is_array($fee['consumptionBreakdown'])) {
            return array_map(
                fn($tranche) => $this->transformTranche($tranche, $rangeMap),
                $fee['consumptionBreakdown']
            );
        }

        if (isset($fee['consumptionBasic'])) {
            return [$this->transformBasicToTranche($fee['consumptionBasic'], $rangeMap)];
        }

        return null;
    }

    /**
     * Transforms a consumptionBasic object into a single-tranche commissions entry.
     * consumptionBasic has no range limits, so con1/con2/pot1/pot2 are all null.
     */
    private function transformBasicToTranche(array $basic, array $rangeMap): array
    {
        $breakdown = $this->buildBreakdown($basic, $rangeMap);
        $base = isset($basic['comAs']) ? (float) $basic['comAs'] : null;

        return [
            'con1'      => null,
            'con2'      => null,
            'pot1'      => null,
            'pot2'      => null,
            'multiply'  => false,
            'base'      => $base,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Transforms a consumptionBreakdown tranche into the new format.
     */
    private function transformTranche(array $tranche, array $rangeMap): array
    {
        $breakdown = $this->buildBreakdown($tranche, $rangeMap);
        $base = isset($tranche['comAs']) ? (float) $tranche['comAs'] : null;

        return [
            'con1'      => $this->parseLimit($tranche['con1'] ?? null),
            'con2'      => $this->parseLimit($tranche['con2'] ?? null),
            'pot1'      => $this->parseLimit($tranche['pot1'] ?? null),
            'pot2'      => $this->parseLimit($tranche['pot2'] ?? null),
            'multiply'  => (bool) ($tranche['multiply'] ?? false),
            'base'      => $base,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Builds the breakdown object mapping rangeId → commission value.
     * comAs is excluded here — it goes to the "base" field instead.
     */
    private function buildBreakdown(array $source, array $rangeMap): array
    {
        $breakdown = [];

        foreach ($rangeMap as $value => $rangeId) {
            $key = "com{$value}";

            if (isset($source[$key])) {
                $breakdown[$rangeId] = (float) $source[$key];
            }
        }

        return $breakdown;
    }

    /**
     * Converts a limit value to a number or null.
     * ">" and null both become null (meaning no upper bound / infinity).
     * Numeric strings are cast to float.
     */
    private function parseLimit(mixed $value): ?float
    {
        if ($value === null || $value === '>' || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
