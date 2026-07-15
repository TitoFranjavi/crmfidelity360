<template>
    <div>

        <!-- Buscador -->
        <div class="form-group mb-20">
            <div class="input-group">
                <input
                    data-size="12"
                    v-model="searchText"
                    type="text"
                    placeholder="Busca tu usuario..."
                >
            </div>
        </div>

        <!-- Listado -->
        <div class="user-list">
            <div
                class="user"
                v-for="user in filteredUserList"
                :key="user._id"
                @click="toggleSelectUser(user)"
            >
                <div class="d-flex">

                    <!-- Imagen perfil -->
                    <div class="my-auto w-10">
                        <img :src="'/assets/profile_images/' + user.profileImage">
                    </div>

                    <!-- Label, nombre, correo -->
                    <div class="content d-flex column mx-10 w-75 ellipsis">

                        <p class="text opacity-3 upper ellipsis" data-size="8">
                            {{ user.label }}
                        </p>

                        <p
                            class="text ellipsis"
                            :data-color="isSelected(user._id) ? 'azul' : ''"
                        >
                            {{ user.firstName }} {{ user.lastName }}
                        </p>

                        <p class="text opacity-3 ellipsis" data-size="8">
                            {{ user.email }}
                        </p>

                    </div>

                    <!-- Botón seleccionar responsable -->
                    <div class="text pointer text-center my-auto w-10">
                        <i
                            class="fas fa-arrow-pointer"
                            :data-color="isSelected(user._id) ? 'azul' : ''"
                        ></i>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    name: "UserListComponent",

    props: {
        basicData: {
            type: Object,
            default: null
        },

        userListSelected: {
            type: Array,
            default: () => []
        },

        userListToSelect: {
            type: Array,
            default: null
        },

        userListToExclude: {
            type: Array,
            default: () => []
        },

        requireOneSelected: {
            type: Boolean,
            default: false
        },

        principalUserId: {
            type: [String, Object],
            default: null
        },

        account: {
            type: Object,
            default: null
        },

        editing: {
            type: Boolean,
            default: false
        }
    },

    emits: [
        'update:userListSelected',
        'toggleSelectUserInOrders'
    ],

    data() {
        return {
            searchText: ''
        }
    },

    watch: {
        basicData: {
            handler() {
                this.setDefaultSelection()
            },
            deep: true,
            immediate: true
        },

        editing: {
            handler() {
                this.setDefaultSelection()
            },
            immediate: true
        },

        principalUserId: {
            handler() {
                this.setDefaultSelection()
            },
            immediate: true
        }
    },

    computed: {
        userListModel: {
            get() {
                return Array.isArray(this.userListSelected)
                    ? this.userListSelected.map(id => this.normalizeId(id)).filter(Boolean)
                    : []
            },

            set(value) {
                const normalizedValue = Array.isArray(value)
                    ? value.map(id => this.normalizeId(id)).filter(Boolean)
                    : []

                this.$emit('update:userListSelected', normalizedValue)
            }
        },

        filteredUserList() {
            if (!this.basicData) return []

            const hasUserList = Array.isArray(this.basicData.userList)
            const hasUserListToSelect = Array.isArray(this.userListToSelect)

            if (!hasUserList && !hasUserListToSelect) return []

            let userListToSelect = []

            const excludedUserIds = (this.userListToExclude || [])
                .map(item => this.normalizeId(item))
                .filter(Boolean)

            const isExcludedUser = user => {
                return excludedUserIds.includes(this.normalizeId(user))
            }

            if (hasUserListToSelect) {
                const allowedUserIds = this.userListToSelect
                    .map(id => this.normalizeId(id))
                    .filter(Boolean)

                userListToSelect = (this.basicData.userListComplete || []).filter(user =>
                    !isExcludedUser(user) &&
                    allowedUserIds.includes(this.normalizeId(user))
                )
            } else {
                if (this.canManage('users.admiWhiHier') && Array.isArray(this.basicData.userListUserSubdomain)) {
                    userListToSelect = this.basicData.userListUserSubdomain
                } else if (this.canManage('users.admiWhiHier') && Array.isArray(this.basicData.subdomainUserList)) {
                    userListToSelect = this.basicData.subdomainUserList
                } else {
                    userListToSelect = [
                        ...(this.basicData.userList || []),
                        ...(this.basicData.userLogged ? [this.basicData.userLogged] : [])
                    ]
                }

                userListToSelect = userListToSelect.filter(user => !isExcludedUser(user))
            }

            let list = []
            const searchTerm = this.searchText.toLowerCase().trim()

            if (searchTerm === '') {
                list = userListToSelect
            } else {
                const options = {
                    includeScore: true,
                    threshold: 0.2,
                    keys: ['fullName', 'firstName', 'lastName', 'email', 'phone', 'dni'],
                    tokenize: true,
                    matchAllTokens: false,
                    ignoreLocation: true
                }

                const usersWithFullName = userListToSelect.map(user => ({
                    ...user,
                    fullName: `${user.firstName || ''} ${user.lastName || ''}`.toLowerCase()
                }))

                const fuseUsers = new Fuse(usersWithFullName, options)

                list = fuseUsers.search(searchTerm).map(result => result.item)
            }

            const selectedIds = this.userListModel

            return list.sort((a, b) => {
                const aSelected = selectedIds.includes(this.normalizeId(a))
                const bSelected = selectedIds.includes(this.normalizeId(b))

                if (aSelected && !bSelected) return -1
                if (!aSelected && bSelected) return 1

                return (a.firstName || '').localeCompare(b.firstName || '') ||
                    (a.lastName || '').localeCompare(b.lastName || '')
            })
        }
    },

    methods: {
        normalizeId(item) {
            if (!item) return ''

            if (typeof item === 'object') {
                return String(item.$oid || item._id || '')
            }

            return String(item)
        },

        isSelected(userId) {
            return this.userListModel.includes(this.normalizeId(userId))
        },

        canManage(code) {
            const user = this.basicData?.userLogged
            const subdomain = this.basicData?.userSubdomain

            if (!user || !subdomain) return false

            const label = user.label
            const labelsPermissions = subdomain.labels_permissions

            if (!label || !labelsPermissions) return false
            if (!labelsPermissions[label]) return false
            if (!code || !code.includes('.')) return false

            const [module, action] = code.split('.')

            const modulePermissions = labelsPermissions[label][module]

            return Array.isArray(modulePermissions) && modulePermissions.includes(action)
        },

        setDefaultSelection() {
            if (!this.editing) return
            if (!this.basicData) return

            // Si ya hay algo seleccionado, no tocar.
            if (Array.isArray(this.userListModel) && this.userListModel.length > 0) return

            const principalId = this.normalizeId(this.principalUserId)

            // Si se pasa principalUserId, se selecciona por defecto.
            if (principalId) {
                this.userListModel = [principalId]
                return
            }

            // Comportamiento anterior.
            if (this.canManage('users.admiWhiHier')) {
                const subdomainUserId = this.normalizeId(this.basicData?.userSubdomain?._id)

                if (subdomainUserId) {
                    this.userListModel = [subdomainUserId]
                }
            }
        },

        toggleSelectUser(user) {
            if (!this.editing) return
            if (!user?._id) return

            const DOIVE = '683d658761231bd1080b4802'
            const EXCEPT = '65cb57489c2c285441086a43'

            const userId = this.normalizeId(user)
            const loggedUserId = this.normalizeId(this.basicData?.userLogged)

            let next = [...this.userListModel]

            const idx = next.indexOf(userId)

            if (idx !== -1) {
                // Si requireOneSelected es true, no permitir dejar la lista vacía.
                if (this.requireOneSelected && next.length === 1) {
                    this.userListModel = [this.principalUserId]
                    return
                }

                next = next.filter(id => id !== userId)
            } else {
                // Regla existente: si no es DOIVE y ya hay alguien distinto de EXCEPT, limpiar.
                if (loggedUserId !== DOIVE && next.some(id => id !== EXCEPT)) {
                    next = next.filter(id => id === EXCEPT)
                }

                next.push(userId)
            }

            // Evitar duplicados.
            next = [...new Set(next)]

            this.userListModel = next

            this.$emit('toggleSelectUserInOrders', userId)
        }
    }
}
</script>

<style scoped>

</style>
