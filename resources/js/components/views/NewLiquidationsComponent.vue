<template>
    <div class="new-liquidations">

        <!-- TABS -->
        <div class="d-flex align-center mb-20" data-gap="10">
            <p
                class="tab-btn pointer"
                :class="{ active: activeTab === 'overview' }"
                @click="activeTab = 'overview'"
            >
                <i class="ti ti-layout-dashboard"></i> Resumen general
            </p>
            <p
                class="tab-btn pointer"
                :class="{ active: activeTab === 'agents' }"
                @click="activeTab = 'agents'"
            >
                <i class="ti ti-users"></i> Agentes
            </p>
            <p
                class="tab-btn pointer"
                :class="{ active: activeTab === 'marketers' }"
                @click="activeTab = 'marketers'"
            >
                <i class="ti ti-building"></i> Comercializadoras
            </p>

            <div class="ml-auto d-flex align-center" data-gap="8">
                <button class="custom-button" data-size="small" data-bg="transparent" style="border: 1px solid rgba(1,44,104,0.2)">
                    <i class="ti ti-file-type-pdf"></i> PDF
                </button>
                <button class="custom-button" data-size="small" data-bg="transparent" style="border: 1px solid rgba(1,44,104,0.2)">
                    <i class="ti ti-table-export"></i> Excel
                </button>
            </div>
        </div>

        <!-- CONTENIDO -->
        <transition name="fade-vertical" mode="out-in">
            <!--<overview-tab v-if="activeTab === 'overview'" key="overview" />-->
            <agent-liquidations-component v-if="activeTab === 'agents'"   key="agents" />
            <marketer-liquidations-component v-else-if="activeTab === 'marketers'" key="marketers" />
        </transition>

    </div>
</template>

<script>

import AgentLiquidationsComponent from "@/components/items/AgentLiquidationsComponent.vue";

export default {
    name: 'NewLiquidationsComponent',
    components: {AgentLiquidationsComponent},

    data() {
        return {
            activeTab: 'overview',
        }
    },
}
</script>

<style scoped>
.new-liquidations {
    padding: 30px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.tab-btn {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 14px;
    color: var(--color-principal);
    opacity: 0.5;
    font-weight: 500;
    border: 1px solid transparent;
    transition: 0.2s;
}

.tab-btn.active {
    opacity: 1;
    border-color: rgba(1, 44, 104, 0.2);
    background: rgba(214, 235, 255, 0.7);
}

.tab-btn:hover:not(.active) {
    opacity: 0.8;
}
</style>
