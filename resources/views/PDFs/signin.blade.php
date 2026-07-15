<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de Fichajes</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 5px;
        }

        p {
            text-align: center;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
        }

        th {
            background: #f3f3f3;
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .employee {
            background: #e8eef9;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>Informe de Fichajes</h2>
    <p><strong>Desde:</strong> {{ $startDate }} &nbsp; | &nbsp; <strong>Hasta:</strong> {{ $endDate }}</p>

    @if ($signins->isEmpty())
    <p>No hay fichajes registrados en este rango de fechas.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Horas totales</th>
                <th>Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($signins as $s)
            <tr>
                <td>{{ $s->user->firstName ?? '' }} {{ $s->user->lastName ?? '' }}</td>
                <td>{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
                <td>{{ $s->entry ?? '-' }}</td>
                <td>{{ $s->exit ?? '-' }}</td>
                <td>
                    @if ($s->entry && $s->exit)
                    @php
                    $entry = \Carbon\Carbon::createFromFormat('H:i', $s->entry);
                    $exit = \Carbon\Carbon::createFromFormat('H:i', $s->exit);

                    // Si la hora de salida es menor que la de entrada, asumimos que es al día siguiente
                    if ($exit->lt($entry)) {
                    $exit->addDay();
                    }

                    $diffMinutes = $entry->diffInMinutes($exit);
                    @endphp

                    {{ floor($diffMinutes / 60) }} h {{ $diffMinutes % 60 }} min
                    @else
                    -
                    @endif
                </td>
                <td>{{ $s->notes ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @php
    use Carbon\Carbon;

    $totalMinutes = 0;

    foreach ($signins as $s) {
        if ($s->entry && $s->exit) {
            try {
                $entry = Carbon::createFromFormat('H:i', $s->entry);
                $exit = Carbon::createFromFormat('H:i', $s->exit);

                // Si la salida es menor que la entrada → cruce de medianoche
                if ($exit->lt($entry)) {
                    $exit->addDay();
                }

                $totalMinutes += $entry->diffInMinutes($exit);
            } catch (\Exception $e) {
                // ignorar errores de formato
            }
        }
    }

    $totalHours = floor($totalMinutes / 60);
    $remainingMinutes = $totalMinutes % 60;
@endphp

@if (!$signins->isEmpty())
    <table style="margin-top: 20px; border-top: 2px solid #333;">
        <tr>
            <td style="text-align: right; font-weight: bold;">
                Total de horas trabajadas:
                {{ $totalHours }} horas {{ sprintf('%02d', $remainingMinutes) }} minutos
            </td>
        </tr>
    </table>
@endif
</body>

</html>