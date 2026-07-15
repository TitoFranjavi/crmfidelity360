<template>
    <div v-if="width < 810" class="my-5">
            <!--Card-->
            <div class="d-flex align-center pointer" @click="seeOrderInfo(order)">

                <div class="mobile-order-row text" data-weight="600">
                    <!--Id-->
                    <span class="mobile-order-id opacity-5" v-if="basicData?.userSubdomain?.settings?.contractsIds">
                        {{ order.identifier ? order.identifier : '-' }}
                    </span>

                    <!--Icono verificación-->
                    <i
                        v-if="order?.verifications?.length > 0"
                        class="mobile-order-icon fa-solid fa-lightbulb"
                        data-color="amarillo"
                    />

                    <!--Nombre-->
                    <span class="mobile-order-name">
                        {{ order.name }}
                    </span>

                    <!--Logo comercializadora-->
                    <div class="mobile-order-logo-box">
                        <img
                            v-if="getMarketerLogo(order.marketer)"
                            :src="getMarketerLogo(order.marketer)"
                            :alt="order.marketer"
                            class="order-marketer-logo"
                        />
                    </div>
                </div>

                <div class="deploy-btn ml-10" data-round="15" :class="{ 'selected': orderInfoSelected == order['_id'] }">
                    <i class="fa-solid" :class="orderInfoSelected == order['_id'] ? 'fa-chevron-up' : 'fa-chevron-down'" />
                </div>
            </div>

            <!--Info card-->
            <div class="d-flex column" v-if="orderInfoSelected === order['_id']">

                <!--Info del contrato-->
                <div class="my-10">
                    <!--Título-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Título</div>
                        <div class="text" data-size="13">{{ order.name }}</div>
                    </div>

                    <!--Agente-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Agente</div>
                        <div class="text" data-size="13">{{ order.owner }}</div>
                    </div>

                    <!--NIF/CIF-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">NIF/CIF</div>
                        <div class="text" data-size="13">{{ order.accountCIF }}</div>
                    </div>

                    <!--Tarifa-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Tarifa</div>
                        <div class="text" data-size="13">{{ order.fee }}</div>
                    </div>

                    <!--Producto-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Producto</div>
                        <div class="text" data-size="13">{{ order.product + order.marketer }}</div>
                    </div>

                    <!--CUPS-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">CUPS</div>
                        <div class="text" data-size="13">{{ order.CUPS }}</div>
                    </div>

                    <!--Estado-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Estado</div>
                        <div class="custom-button w-fit-content"
                             data-size="small"
                             data-color="principal"
                             :data-mode="!isHex(status.color) ? 'translucent' : null"
                             :data-bg="!isHex(status.color) ? status.color : null"
                             :style="isHex(status.color) ? { backgroundColor: hexToRgba(status.color, 0.1), border: `1px solid ${status.color}` } : {}">
                            <p class="w-70-px-max ellipsis">{{ status.title }}</p>
                        </div>
                    </div>

                    <!--Fec. activación-->
                    <div class="d-flex justify-between">
                        <div class="text" data-size="13" data-weight="600">Fec. activación</div>
                        <div class="text" data-size="13">{{ getPrettyDateTransfer(order.activationDate) }}</div>
                    </div>

                    <!--Ult. actualización / Fec. traspaso (onex)-->
                    <div class="d-flex justify-between">
                        <template v-if="['692da6aeaedb25b428042132','6a47777b04ce48688d831e57'].includes(basicData?.userSubdomain?._id?.$oid ?? basicData?.userSubdomain?._id)">
                            <div class="text" data-size="13" data-weight="600">Fec. traspaso</div>
                            <div class="text" data-size="13">{{ getPrettyTransferDate(order.transferDate) }}</div>
                        </template>
                        <template v-else>
                            <div class="text" data-size="13" data-weight="600">Ult. actualización</div>
                            <div class="text" data-size="13">{{ getPrettyDateTransfer(order.lastUpdate) }}</div>
                        </template>
                    </div>

                    <!--Comisión agente (solo onex)-->
                    <div class="d-flex justify-between"
                         v-if="['692da6aeaedb25b428042132','6a47777b04ce48688d831e57'].includes(basicData?.userSubdomain?._id?.$oid ?? basicData?.userSubdomain?._id)">
                        <div class="text" data-size="13" data-weight="600">Comisión agente</div>
                        <div class="text" data-size="13">{{ getPrettyAgentCommission(order) }}</div>
                    </div>
                </div>

                <!--Botones-->
                <div class="d-flex column" data-gap="8">

                    <div @click="selectOrderToSee"
                         class="custom-button w-100" data-bg="principal" data-mode="outline"
                         data-align="center" data-size="small" data-weight="700"><i class="fas fa-eye mr-5"></i> Ver contrato
                    </div>

                    <div @click="deleteOrder(order)" v-if="!isReadOnly" class="custom-button w-100" data-bg="rojo" data-mode="outline" data-align="center" data-size="small" data-weight="700">
                        <i class="fa-regular fa-trash mr-5" /> Eliminar
                    </div>
                </div>
            </div>
        </div>
    <div v-else class="d-flex column">

        <!-- KUVI -->
        <div v-if="basicData?.userSubdomain?._id === '6909faa9232c09035a03f3b2'"
             :class="['contracts-grid contracts-row-align fidelity-contracts-row pointer',{'with-id' : basicData?.userSubdomain?.settings?.contractsIds}]">

            <!--Id-->
            <a class="d-flex profile-image"
               v-if="basicData?.userSubdomain?.settings?.contractsIds"
               v-on:dblclick="selectOrderToSee"
               @click.prevent
               :href="`/orders?_id=${order._id['$oid']}`"
               rel="noopener">
                <p class="mr-5" data-color="amarillo" v-if="!!order.assignedTo && otherSubdomainImg">
                    <img class="profile-image pointer w-20-px h-20-px round"
                         data-round="5"
                         :src="'/assets' + otherSubdomainImg"
                         alt="Logo subdominio">
                </p>
                <p data-color="azul" data-weight="600">
                    <i v-if="order.verifications && order.verifications.length > 0"
                       class="fa-solid fa-lightbulb mr-5"
                       data-color="amarillo"></i>
                    {{ order.identifier ? order.identifier : '-' }}
                </p>
            </a>

            <!--Título-->
            <a class="d-flex profile-image fidelity-contract-title"
               v-on:dblclick="selectOrderToSee"
               @click.prevent
               :href="`/orders?_id=${order._id['$oid']}`"
               rel="noopener">
                <p class="mr-5"
                   data-color="amarillo"
                   v-if="!!order.assignedTo && otherSubdomainImg && basicData.userSubdomain && basicData.userSubdomain.settings && !basicData.userSubdomain.settings.contractsIds">
                    <img class="profile-image pointer w-20-px h-20-px round"
                         data-round="5"
                         :src="'/assets' + otherSubdomainImg"
                         alt="Logo subdominio">
                </p>
                <p class="fidelity-contract-title-text" data-color="azul" data-weight="600">
                    <i v-if="order.verifications && order.verifications.length > 0 && basicData.userSubdomain && basicData.userSubdomain.settings && !basicData.userSubdomain.settings.contractsIds"
                       class="fa-solid fa-lightbulb mr-5"
                       data-color="amarillo"></i>
                    {{ order.name ? order.name : '-' }}
                </p>
            </a>

            <div class="d-flex column">
                <p class="text">{{ hierarchy.superusuario }}</p>
            </div>

            <div class="d-flex column">
                <p class="text">{{ hierarchy.usuario }}</p>
            </div>

            <div class="d-flex column">
                <p class="text">{{ hierarchy.comercial }}</p>
            </div>

            <!--CIF-->
            <div class="d-flex column">
                <p class="text">{{ order.accountCIF ? order.accountCIF : '-' }}</p>
            </div>

            <!--Tarifa-->
            <div class="d-flex column">
                <p class="text">{{ order.fee ? order.fee : '-' }}</p>
                <p class="text" v-if="order.productType === 'cd'">{{ order.feeSecondary ? order.feeSecondary : '-' }}</p>
            </div>

            <!--Producto-->
            <div class="d-flex column">
                <p class="text fidelity-contract-product-text">
                    {{ order.product ? (order.product + ' ' + (order.marketer === '' ? 'Sin comercializadora' : order.marketer)) : '-' }}
                </p>
                <p class="text fidelity-contract-product-text" v-if="order.productType === 'cd'">
                    {{ order.productSecondary ? (order.productSecondary + ' ' + (order.marketer === '' ? 'Sin comercializadora' : order.marketer)) : '-' }}
                </p>
            </div>

            <!--CUPS-->
            <div class="d-flex column">
                <p class="text">{{ order.CUPS ? order.CUPS : '-' }}</p>
                <p class="text" v-if="order.productType === 'cd'">{{ order.CUPSSecondary ? order.CUPSSecondary : '-' }}</p>
            </div>

            <!--Estado-->
            <div class="d-flex column" v-if="status">
                <div class="custom-button text-center mx-auto"
                     data-size="small"
                     data-color="principal"
                     :data-mode="!isHex(status.color) ? 'translucent' : null"
                     :data-bg="!isHex(status.color) ? status.color : null"
                     :style="isHex(status.color) ? { backgroundColor: hexToRgba(status.color, 0.1), border: `1px solid ${status.color}` } : {}">
                    <p>{{ status.title }}</p>
                </div>
            </div>

            <!--Fec. creación-->
            <div class="d-flex column">
                <p class="text">{{ getPrettyDateTime(order.createdAt) }}</p>
            </div>

            <!--Fec. activación-->
            <div class="d-flex column">
                <p class="text">{{ getPrettyDate(order.activationDate) }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex" v-if="!isReadOnly">
                <a class="mx-10 text pointer" @click.stop="selectOrderToSee" @click.prevent :href="`/orders?_id=${order._id['$oid']}`">
                    <i class="far fa-eye"></i>
                </a>

                <div v-if="canManage('contracts.delete')"
                     class="mx-10 text pointer"
                     data-color="rojo"
                     v-on:click.stop.prevent="deleteOrder(order)">
                    <i class="far fa-trash"></i>
                </div>
            </div>

            <div v-else></div>

        </div>

        <!-- Resto de subdominios -->
        <div v-else
             class="contact six-no-check pointer"
             :class="{'with-id' : basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds}">

            <!--Id-->
            <a class="d-flex profile-image"
               v-if="basicData.userSubdomain && basicData.userSubdomain.settings && basicData.userSubdomain.settings.contractsIds"
               v-on:dblclick="selectOrderToSee"
               @click.prevent
               :href="`/orders?_id=${order._id['$oid']}`"
               rel="noopener">
                <p class="mr-5" data-color="amarillo" v-if="!!order.assignedTo && otherSubdomainImg">
                    <img class="profile-image pointer w-20-px h-20-px round"
                         data-round="5"
                         :src="'/assets' + otherSubdomainImg"
                         alt="Logo subdominio">
                </p>
                <p class="ellipsis" data-color="azul" data-weight="600">
                    <i v-if="order.verifications && order.verifications.length > 0"
                       class="fa-solid fa-lightbulb mr-5"
                       data-color="amarillo"></i>
                    {{ order.identifier ? order.identifier : '-' }}
                </p>
            </a>

            <!--Título-->
            <a class="d-flex profile-image"
               v-on:dblclick="selectOrderToSee"
               @click.prevent
               :href="`/orders?_id=${order._id['$oid']}`"
               rel="noopener">
                <p class="mr-5"
                   data-color="amarillo"
                   v-if="!!order.assignedTo && otherSubdomainImg && basicData.userSubdomain && basicData.userSubdomain.settings && !basicData.userSubdomain.settings.contractsIds">
                    <img class="profile-image pointer w-20-px h-20-px round"
                         data-round="5"
                         :src="'/assets' + otherSubdomainImg"
                         alt="Logo subdominio">
                </p>
                <p class="ellipsis" data-color="azul" data-weight="600">
                    <i v-if="order.verifications && order.verifications.length > 0 && basicData.userSubdomain && basicData.userSubdomain.settings && !basicData.userSubdomain.settings.contractsIds"
                       class="fa-solid fa-lightbulb mr-5"
                       data-color="amarillo"></i>
                    {{ order.name ? order.name : '-' }}
                </p>
            </a>

            <!--Agente-->
            <div class="d-flex column">
                <p class="text ellipsis">{{ order.owner ? order.owner : '-' }}</p>
            </div>

            <!--CIF-->
            <div class="d-flex column">
                <p class="text ellipsis">{{ order.accountCIF ? order.accountCIF : '-' }}</p>
            </div>

            <!--Tarifa-->
            <div class="d-flex column">
                <p class="text ellipsis">{{ order.fee ? order.fee : '-' }}</p>
                <p class="text ellipsis" v-if="order.productType === 'cd'">{{ order.feeSecondary ? order.feeSecondary : '-' }}</p>
            </div>

            <!--Producto-->
            <div class="d-flex column">
                <p class="text ellipsis">
                    {{ order.product ? (order.product + ' ' + (order.marketer === '' ? 'Sin comercializadora' : order.marketer)) : '-' }}
                </p>
                <p class="text ellipsis" v-if="order.productType === 'cd'">
                    {{ order.productSecondary ? (order.productSecondary + ' ' + (order.marketer === '' ? 'Sin comercializadora' : order.marketer)) : '-' }}
                </p>
            </div>

            <!--CUPS-->
            <div class="d-flex column">
                <p class="text ellipsis">{{ order.CUPS ? order.CUPS : '-' }}</p>
                <p class="text ellipsis" v-if="order.productType === 'cd'">{{ order.CUPSSecondary ? order.CUPSSecondary : '-' }}</p>
            </div>

            <!--Estado-->
            <div class="d-flex column ellipsis" v-if="status">
                <div class="custom-button text-center w-90-px mr-auto"
                     data-size="small"
                     data-color="principal"
                     :data-mode="!isHex(status.color) ? 'translucent' : null"
                     :data-bg="!isHex(status.color) ? status.color : null"
                     :style="isHex(status.color) ? { backgroundColor: hexToRgba(status.color, 0.1), border: `1px solid ${status.color}` } : {}">
                    <p class="w-70-px-max ellipsis">{{ status.title }}</p>
                </div>
            </div>

            <!--Ult. estado / Fec. activación-->
            <div class="d-flex column">
                <p class="text ellipsis">
                    {{ getPrettyDateTransfer(order.activationDate) }}
                </p>
            </div>

            <!--Ult. actualización / Fec. traspaso (onex)-->
            <div class="d-flex column">
                <p class="text ellipsis" v-if="['692da6aeaedb25b428042132','6a47777b04ce48688d831e57'].includes(basicData?.userSubdomain?._id?.$oid ?? basicData?.userSubdomain?._id)">{{ getPrettyTransferDate(order.transferDate) }}</p>
                <p class="text ellipsis" v-else>{{ getPrettyDateTransfer(order.lastUpdate) }}</p>
            </div>

            <!--Comisión agente (solo onex)-->
            <div class="d-flex column"
                 v-if="['692da6aeaedb25b428042132','6a47777b04ce48688d831e57'].includes(basicData?.userSubdomain?._id?.$oid ?? basicData?.userSubdomain?._id)">
                <p class="text ellipsis onex-comm-col">{{ getPrettyAgentCommission(order) }}</p>
            </div>

            <!--Botones-->
            <div class="d-flex" v-if="!isReadOnly">
                <a class="mx-10 text pointer" @click.stop="selectOrderToSee" @click.prevent :href="`/orders?_id=${order._id['$oid']}`">
                    <i class="far fa-eye"></i>
                </a>
                <div v-if="canManage('contracts.delete')"
                     class="mx-10 text pointer"
                     data-color="rojo"
                     v-on:click.stop.prevent="deleteOrder(order)">
                    <i class="far fa-trash"></i>
                </div>
            </div>

        </div>

        <!--Linea separadora-->
        <div class="separator my-10"></div>
    </div>
</template>

<script>
import {useWindowSize} from "@vueuse/core";

export default {
    name: "OrderCardComponent",

    props: [
        'order',
        'account',
        'isReadOnly',
        'basicData',
        'seeOrderInfo',
        'orderInfoSelected',
        'marketers'
    ],

    data() {
        return {
            statuses: [
                { code: 'r', title: 'Recibido', color: 'receivedStatus', limitedTo: [] },
                { code: 'p', title: 'Pendiente', color: 'pendingStatus', limitedTo: [] },
                { code: 't', title: 'Tramitado', color: 'processedStatus', limitedTo: [] },
                { code: 'f', title: 'Firmado', color: 'signedStatus', limitedTo: [] },
                { code: 'fc', title: 'Firmado - Llamada verificada', color: 'signedAndVerifiedCallStatus', limitedTo: [] },
                { code: 'ac', title: 'Aceptado', color: 'aceptedStatus', limitedTo: [] },
                { code: 'ap', title: 'Pendiente de activacion', color: 'activatedPendingStatus', limitedTo: [] },
                { code: 'a', title: 'Activado', color: 'activatedStatus', limitedTo: [] },
                { code: 'c', title: 'Comisionado', color: 'commissionedStatus', limitedTo: [] },
                { code: 'i', title: 'Incidencia', color: 'incidenceStatus', limitedTo: [] },
                { code: 's', title: 'Scoring', color: 'scoringStatus', limitedTo: [] },
                { code: 'b', title: 'Baja', color: 'lowStatus', limitedTo: [] },
                { code: 'bo', title: 'Borrador', color: 'lowStatus', limitedTo: [] },
                { code: 'an', title: 'Anulado', color: 'morado', limitedTo: [] },
            ],
        }
    },

    methods: {
        getSubdomainUserList() {
            return this.basicData?.userListComplete;
            /*return Array.isArray(this.basicData?.subdomainUserList)
                ? this.basicData.subdomainUserList
                : [];*/
        },

        getOrderUserId() {
            if (this.order?.userId) return this.order.userId;
            if (this.order?.userid) return this.order.userid;

            if (Array.isArray(this.order?.usersIds) && this.order.usersIds.length) {
                return this.order.usersIds[0];
            }

            return null;
        },

        normalizeLabel(label) {
            return String(label || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();
        },

        getFullName(user) {
            if (!user) return '-';

            return `${user.firstName || ''} ${user.lastName || ''}`
                .replace(/\s+/g, ' ')
                .trim() || '-';
        },

        findUserById(userId) {
            if (!userId) return null;

            return this.getSubdomainUserList()?.find(user => {
                return String(user._id) === String(userId);
            }) || null;
        },

        findUserByLabelInHierarchy(userId, targetLabel) {
            const target = this.normalizeLabel(targetLabel);
            let currentUser = this.findUserById(userId);
            const visited = new Set();

            while (currentUser && !visited.has(String(currentUser._id))) {
                visited.add(String(currentUser._id));

                if (this.normalizeLabel(currentUser.label) === target) {
                    return currentUser;
                }

                const parentId = Array.isArray(currentUser.responsibles)
                    ? currentUser.responsibles[0]
                    : null;

                if (!parentId) break;

                currentUser = this.findUserById(parentId);
            }

            return null;
        },

        getHierarchyUsers() {
            const userId = this.getOrderUserId();

            if (!userId) {
                return {
                    comercial: '-',
                    usuario: '-',
                    superusuario: '-'
                };
            }

            const user = this.findUserById(userId);

            if (!user) {
                return {
                    comercial: '-',
                    usuario: '-',
                    superusuario: '-'
                };
            }

            const comercial = this.findUserByLabelInHierarchy(user._id, 'Comercial');
            const usuario = this.findUserByLabelInHierarchy(user._id, 'Usuario');
            const superusuario = this.findUserByLabelInHierarchy(user._id, 'Súper usuario');

            return {
                comercial: comercial ? this.getFullName(comercial) : '-',
                usuario: usuario ? this.getFullName(usuario) : '-',
                superusuario: superusuario ? this.getFullName(superusuario) : '-'
            };
        },

        canManage(code) {
            const user = this.basicData?.userLogged;
            const subdomain = this.basicData?.userSubdomain;

            if (!user || !subdomain) return false;

            const label = user.label;
            const labelsPermissions = subdomain.labels_permissions;

            if (!label || !labelsPermissions) return false;
            if (!labelsPermissions[label]) return false;
            if (!code || !code.includes('.')) return false;

            const [module, action] = code.split('.');
            const modulePermissions = labelsPermissions[label][module];

            return Array.isArray(modulePermissions) && modulePermissions.includes(action);
        },

        deleteOrder(order) {
            this.$emit('deleteOrder', order);
        },
        seeOrderInfo(order){
            this.$emit('seeOrderInfo', order);
        },
        getInitials(name) {
            if (!name) return '';

            const nameSplited = name.split(/\s+/);
            let initials = nameSplited[0]?.[0] || '';

            if (nameSplited[1]) initials += nameSplited[1][0];

            return initials;
        },
        getLastStatusDate() {
            if (!Array.isArray(this.order?.statuses) || !this.order.statuses.length) {
                return '-';
            }

            const recentStatus = this.order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            });

            const dateNow = new Date(recentStatus.date);

            if (isNaN(dateNow.getTime())) return '-';

            const day = String(dateNow.getDate()).padStart(2, '0');
            const month = String(dateNow.getMonth() + 1).padStart(2, '0');
            const year = dateNow.getFullYear();

            return `${day}/${month}/${year}`;
        },

        getPrettyDate(date) {
            if (!date) return '-';

            const dateNow = new Date(date);

            if (isNaN(dateNow.getTime())) return '-';

            const day = String(dateNow.getDate()).padStart(2, '0');
            const month = String(dateNow.getMonth() + 1).padStart(2, '0');
            const year = dateNow.getFullYear();

            return `${day}/${month}/${year}`;
        },

        getPrettyDateTime(date) {
            if (!date) return '-';

            return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD/MM/YYYY HH:mm");
        },

        getPrettyDateTransfer(date) {
            if (!date) return '-';

            return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD/MM/YYYY");
        },
        getAgentCommission(order) {
            const breakdown = order?.commissions?.breakdown;
            if (!Array.isArray(breakdown) || !breakdown.length) return 0;

            const agentId = order?.usersIds?.[0] ?? null;
            const entry = agentId
                ? breakdown.find(item => item?.userId === agentId)
                : null;

            return entry ? (Number(entry.commission) || 0) : 0;
        },

        getPrettyAgentCommission(order) {
            const value = Math.round(this.getAgentCommission(order));
            const unit = this.basicData?.userLogged?.commInPoints ? ' pts.' : ' €';
            return value.toLocaleString('es-ES') + unit;
        },

        getPrettyTransferDate(date) {
            if (!date) return '-';

            const parsed = moment(
                date,
                ["DD/MM/YY", "DD/MM/YYYY", "YYYY-MM-DD HH:mm:ss", "YYYY-MM-DD", moment.ISO_8601],
                true
            );

            return parsed.isValid() ? parsed.format("DD/MM/YYYY") : date;
        },

        isHex(color) {
            return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(color);
        },

        hexToRgba(hex, alpha = 1) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);

            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        },

        selectOrderToSee() {
            this.$emit('selectOrderToSee', this.order);
        },

        actionLink(route) {
            this.$router.push(route);
        },
        getMarketerLogo(orderMarketer) {
            console.log(this.marketers)
            if (!orderMarketer || !this.marketers || this.marketers.length === 0) {
                return null;
            }
            console.log(orderMarketer)

            const normalize = (value) => {
                return String(value || "")
                    .trim()
                    .toLowerCase()
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "");
            };

            const marketerFound = this.marketers.find((marketer) => {
                return normalize(marketer.name) === normalize(orderMarketer);
            });

            if (!marketerFound || !marketerFound.logo) {
                return null;
            }

            console.log(marketerFound.logo);
            return "/assets/marketers_logo/" + marketerFound.logo;
        },
    },

    computed: {
        hierarchy() {
            return this.getHierarchyUsers();
        },

        status() {
            if (!this.basicData || !this.basicData.userSubdomain) return '';

            if (!Array.isArray(this.order?.statuses) || !this.order.statuses.length) {
                return '';
            }

            const recentStatus = this.order.statuses.reduce((latest, current) => {
                return new Date(current.date) > new Date(latest.date) ? current : latest;
            });

            if (!Array.isArray(this.basicData.userSubdomain.statuses)) return '';

            return this.basicData.userSubdomain.statuses.find((status) => {
                return status.code === recentStatus.code;
            });
        },

        time() {
            return moment(this.order.createdAt).format('DD/MM/YYYY');
        },

        otherSubdomainImg() {
            const userId = this.getOrderUserId();

            if (!userId) return null;

            let user = this.findUserById(userId);
            const userListComplete = this.basicData?.userListComplete ?? [];

            if (!user || !Array.isArray(userListComplete) || userListComplete.length === 0) return null;

            user = this.$utilities.obtainSubdomainUser(user._id, userListComplete);

            if (!user || !user.profileImage) return null;

            return '/profile_images/' + user.profileImage;
        }
    },
    setup() {
        const { width } = useWindowSize()
        return { width }
    },
}
</script>

<style scoped>
    /* Comisión (solo onex): texto más pequeño para que quepa en la fila */
    .onex-comm-col {
        font-size: 11px;
    }

    .contracts-row-align {
        padding-left: 12px;
        padding-right: 12px;
        gap: 6px;
    }

    .contracts-row-align > .d-flex,
    .contracts-row-align > a {
        justify-content: center;
        text-align: center;
        min-width: 0;
    }

    .contracts-row-align > .d-flex.column {
        align-items: center;
    }

    .contracts-row-align p {
        text-align: center;
        word-break: break-word;
    }

    .contracts-row-align .ellipsis {
        white-space: normal;
        overflow: visible;
        text-overflow: initial;
    }

    .mobile-order-row {
        display: flex;
        align-items: center;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        gap: 6px;
        overflow: hidden;
    }

    .mobile-order-id {
        flex: 0 0 auto;
        max-width: 55px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mobile-order-icon {
        flex: 0 0 auto;
    }

    .mobile-order-name {
        flex: 1 1 auto;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mobile-order-logo-box {
        flex: 0 0 30px;
        width: 30px;
        min-width: 30px;
        max-width: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .order-marketer-logo {
        width: 30px;
        height: 30px;
        max-width: 30px;
        max-height: 30px;
        object-fit: contain;
        display: block;
    }
</style>