<template>
    <div class="content-white" v-on:click="hideCustomSelects">

        <!--Header-->
        <div class="d-flex justify-between align-center">

            <!--Título-->
            <div class="text" data-size="30" data-weight="700">{{ $route.meta.title }}</div>

            <!--Botones-->
            <div class="d-flex" v-if="canManage('documents.upload') && (!loading && !!mainFolderKey) && (!seeZocoDocs || basicData?.userLogged?._id === '65cb57489c2c285441086a43' )">
                <div class="custom-button mr-20" data-size="regular" data-bg="principal" v-on:click="isFloatingBoxDisplayed = true">Añadir carpeta</div>
                <div class="custom-button" data-size="regular" data-bg="principal" v-on:click="openDialog">Subir archivo</div>
            </div>
        </div>


        <!--Barra de busqueda y alternancia de estilo-->
        <div v-if="!loading" class="mt-30 d-flex justify-around select-line">

            <!--Alternar docs Zoco-->
            <div class="d-flex justify-between" v-if="canSeeZoco">

                <div class="d-flex my-5">

                    <div class="custom-checkbox my-auto mr-10" v-on:click="toggleSeeDocs">
                        <div v-bind:class="{selected: seeZocoDocs }"></div>
                    </div>

                    <p class="my-auto mr-15 text-nowrap" data-color="principal" data-size="10">Ver documentos de Zoco Energía SL</p>
                </div>
            </div>

            <!--barra de busqueda-->
            <div class="search-div d-flex">

                <div class="search-bar w-100 documents w-70">

                    <i class="fa-regular fa-magnifying-glass mx-auto my-auto mr-10"></i>

                    <input type="text" placeholder="Busca una carpeta o archivo..." v-model="searchFileText">
                </div>

                <i data-color="principal" class="pointer fas fa-trash my-10 ml-10" v-on:click="resetSearch"></i>

            </div>


            <!--alternancia estilo-->
            <!--<div class="toggle-visibility-buttons">
                <p class="start text pointer" data-size="15" v-on:click="selectedVisibility = 0"><i class="fas fa-bars" v-bind:class="{'selected': selectedVisibility === 0}"></i></p>
                <p class="end text pointer" data-size="15" v-on:click="selectedVisibility = 1"><i class="fas fa-grid-2" v-bind:class="{'selected': selectedVisibility === 1}"></i></p>
            </div>-->
        </div>

        <!--Ordenación-->
        <div v-if="!loading && !!mainFolderKey" class="d-flex justify-end my-10">

            <!--Ordenamiento-->
            <div class="d-flex">
                <div class="text">Ordenar:</div>

                <!--Contenedor cuentas-->
                <div class="custom-select no-hover" v-on:click.stop="seeFilters('sort')" v-bind:class="{'seeing': isSeeingFiltersPc.sort}">

                    <div class="ml-10" data-color="azul">{{ orderTypeSelected.title }} <i class="fas fa-chevron-down ml-10"></i></div>

                    <div class="select-content">
                        <div v-for="orderType in filters.radio.sortBy.data" class="d-flex align-center">

                            <div class="custom-checkbox mr-10" v-on:click="selectOrderType(orderType)" v-bind:class="{ 'selected': orderType.value === filters.radio.sortBy.checked }"></div>

                            <div class="text">{{ orderType.title }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!--Ruta hasta carpeta actual-->
        <div class="d-flex my-20" v-if="path.length > 1">

            <div v-for="(folder, folderInd) in path" class="d-flex">

                <p class="text pointer" data-size="15" v-on:click="folderInd !== path.length - 1 ? changeFolderFromRoute(folder) : ''">{{ folder.name }}</p>

                <div class="text mx-20" v-if="folderInd !== path.length - 1" data-size="15"><i class="fas fa-chevron-right"></i></div>
            </div>
        </div>


        <!--Si no hay vinculado cuenta drive-->
        <div v-if="!loading && !mainFolderKey">
            <div class="text text-center opacity-5">Solicita tu drive</div>

        </div>


        <!--Si hay cuenta vinculada-->
        <div v-else-if="!loading && !!mainFolderKey">

            <!--si esta seleccionado vista en línea-->
            <div v-if="selectedVisibility === 0" class="mt-40">

                <!--cada uno de las carpetas/archivos-->
                <div class="pointer" v-for="fileOrFolder in filteredContent" v-on:click="fileOrFolder.type === 'carpeta' ? changeFolder(fileOrFolder) : previewFile(fileOrFolder)" v-on:dblclick="fileOrFolder.type === 'carpeta' ? changeFolder(fileOrFolder) : seeFile(fileOrFolder)">

                    <div class="d-flex mx-30">

                        <!--Icono-->
                        <div class="mr-20"><i class="text" :data-color="fileOrFolder.type === 'carpeta' ? 'amarillo' : 'principal'" :class="getIcon(fileOrFolder.type)"></i></div>

                        <!--Nombre de carpeta o archivo-->
                        <p class="text ellipsis">{{ fileOrFolder.name }}</p>


                        <!--Fecha última modificación-->
                        <div class="mx-15 nowrap" v-if="fileOrFolder.type !== 'carpeta'">
                            <p class="text opacity-2">{{ getPrettyDate(fileOrFolder.lastModifiedTime) }}</p>
                        </div>

                        <!--botones-->
                        <div class="ml-auto d-flex">

                            <!--visualizar-->
                            <a class="pointer mr-10" v-if="fileOrFolder.type !== 'carpeta'" v-on:click.stop="previewFile(fileOrFolder)"><i class="text far fa-sunglasses"></i></a>

                            <!--descargar-->
                            <a class="pointer mr-10" v-if="fileOrFolder.type !== 'carpeta'" v-on:click.stop="downloadFile(fileOrFolder)"><i class="text far fa-download"></i></a>


                            <!--actualizar-->
                            <p class="pointer mx-10" v-if="canManage('documents.edit')" v-on:click.stop="openUpdateWindow(fileOrFolder)"><i class="text far fa-pencil" data-color="principal"></i></p>


                            <!--borrar-->
                            <p class="pointer mx-10" v-if="canManage('documents.delete')" v-on:click.stop="deleteFileOrFolder(fileOrFolder)"><i class="text far fa-trash" data-color="rojo"></i></p>
                        </div>
                    </div>

                    <div class="separator my-10"></div>
                </div>


                <!--si no hay nada-->
                <div v-if="!filteredContent || filteredContent.length === 0" class="text text-center opacity-5">¡No hay ningún archivo ni carpeta todavia!</div>

            </div>

            <div v-else class="mt-40">
                Vista grid bonita
            </div>


            <!--ventana flotante para introducir nombre de carpeta-->
            <div class="floating-box" v-if="isFloatingBoxDisplayed">

                <div class="register-pos small">

                    <div class="top-part">
                        <div class="form">
                            <div v-bind:class="{ wrong: errors.name}" class="form-group">
                                <p class="my-auto"><label>Nombre de carpeta</label> <span data-color="rojo">*</span></p>
                                <div class="input-group">
                                    <input v-on:focus="delete errors['name']" data-size="12" v-model="folderName" type="text">
                                </div>
                                <span v-if="errors.name" class="error">{{ errors.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!--Separator-->
                    <div class="separator"></div>

                    <!--Botón guardar-->
                    <div class="btn-part justify-between">
                        <div class="custom-button" data-size="small" data-bg="rojo" v-on:click="cancelFolder">Cancelar</div>
                        <div class="custom-button" data-size="small" v-if="!fileOrFolderToUpdate" v-on:click="createFolder">Confirmar</div>
                        <div class="custom-button" data-size="small" v-else v-on:click="updateFileOrFolder">Actualizar</div>
                    </div>
                </div>
            </div>


            <!--ventana añadir archivo-->
            <input id="file" type="file" multiple style="display: none" v-on:change="pickupFiles">

        </div>


        <!--Cargando-->
        <div class="loader-box" v-if="loading">
            <div class="loader"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: "DocumentsComponent",
    props:['basicData'],
    data(){
        return{
            mainFolderKey: '',
            path: [],
            actualFolder: {},
            currentFolderKey: '',
            folderContent: '',
            searchFileText: '',
            selectedVisibility: 0,
            isFloatingBoxDisplayed: false,
            folderName: '',
            files: [],
            errors: {},
            loading: false,
            isSeeingFiltersPc:{
                sort: false
            },
            filters: {
                checkbox: {
                },
                radio: {
                    sortBy: {
                        title: 'Ordenar por',
                        checked: 0,
                        data: [
                            {
                                title: 'Por nombre (A-Z)',
                                value: 0
                            },
                            {
                                title: 'Por nombre (Z-A)',
                                value: 1
                            },
                            {
                                title: 'Más antiguo',
                                value: 2
                            },
                            {
                                title: 'Más reciente',
                                value: 3
                            },
                        ]
                    }
                }
            },
            fileOrFolderToUpdate: '',
            privateFolders:[ //Carpetas privadas ( habría que crearlas por usuario )
                {
                    'folderId': '138Wpy0b8nOAOAFUmJpe_vg3QotQcMKzg',
                    'users': ['franpergar02@gmail.com', 'comercial@asercordenergia.com']
                }
            ],
            users:[],
            userSubdomain: null,
            seeZocoDocs: false
        }
    },
    created() {
    },
    mounted() {

        if (this.basicData.userLogged) //añado que no se haya metido todavia los datos
            this.getGoogleDriveData()

        //this.fetchFolder()
    },
    watch:{
        'basicData.userLogged': function(newVal, oldVal){
            if (newVal){
                this.getGoogleDriveData()
            }
        }
    },
    methods:{
        async getGoogleDriveData(){

            this.path = []

            //this.loading = true;
            //Compruebo si el iniciado es tipo subdominio
            if (this.seeZocoDocs){

                //Asigno los datos
                this.userSubdomain = this.basicData.userListComplete.find((userNow) => userNow._id === '65cb57489c2c285441086a43')

                this.mainFolderKey = this.userSubdomain.drive.folderKey;

                this.path.push({
                    id: this.mainFolderKey,
                    name: '...'
                })

                this.actualFolder = {
                    id: this.mainFolderKey,
                    name: '...'
                }

                this.currentFolderKey = this.mainFolderKey

                //Cargo la carpeta
                this.fetchFolder()

            }else if(this.basicData.userLogged.label === 'Usuario subdominio'){

                //Más para adelante comprobaré si no lo tiene asignado todavia que le salga el tutorial

                //Asigno los datos
                this.userSubdomain = this.basicData.userLogged

                this.mainFolderKey = this.userSubdomain.drive.folderKey;

                this.path.push({
                    id: this.mainFolderKey,
                    name: '...'
                })

                this.actualFolder = {
                    id: this.mainFolderKey,
                    name: '...'
                }

                this.currentFolderKey = this.mainFolderKey

                //Cargo la carpeta
                this.fetchFolder()

            }else{//tendrá que buscar por encima

                //console.log('mira pa ensima')

                //Saco el usuario subdominio ( del que se va a mirar el drive )
                this.userSubdomain = this.findSubdomainUser(this.basicData.userLogged._id, this.basicData.userListTop)

                if (this.userSubdomain){

                    this.mainFolderKey = this.userSubdomain.drive.folderKey;

                    console.log(this.userSubdomain.drive.folderKey)

                    this.path.push({
                        id: this.mainFolderKey,
                        name: '...'
                    })

                    this.actualFolder = {
                        id: this.mainFolderKey,
                        name: '...'
                    }

                    this.currentFolderKey = this.mainFolderKey

                    //Cargo la carpeta
                    this.fetchFolder()

                }else{
                    console.log('USER SUBDOMAIN NOT FOUND')
                }
                console.log('USUARIO SUBDOMINIO --> ',this.userSubdomain)

            }
        },
        findSubdomainUser(currentUserId, users) {
            // Función auxiliar para encontrar un usuario por su _id
            let getUserById = (_id) => users.find(user => user._id === _id);

            let currentUser = getUserById(currentUserId); // Usuario inicial
            let visited = new Set(); // Para evitar ciclos en la jerarquía

            // Recorrer la jerarquía hacia arriba
            while (currentUser) {
                // Evitar loops infinitos
                if (visited.has(currentUser._id)) {
                    console.warn('Se detectó un ciclo en la jerarquía.');
                    break;
                }
                visited.add(currentUser._id);

                // Verificar si el usuario actual tiene label = 'Usuario subdominio'
                if (currentUser.label === 'Usuario subdominio') {
                    return currentUser; // Usuario encontrado
                }

                // Subir en la jerarquía tomando el primer responsable
                let nextId = currentUser.responsibles[0]; // Escoge el primer responsable
                currentUser = getUserById(nextId); // Siguiente usuario en la jerarquía
            }

            // Si no se encontró ningún usuario con el label requerido
            return null;
        },
        async fetchFolder(){

            this.loading = true

            await axios.post(`/api/documents`, {id: this.currentFolderKey, userSubdomain: this.userSubdomain})
                .then((res) => {
                    this.folderContent = res.data.results

                })
                .catch((err) => {
                    console.log(err)
                })
                .finally(() => {
                    this.loading = false
                })
        },
        toggleSeeDocs(){
            this.seeZocoDocs = !this.seeZocoDocs;

            this.getGoogleDriveData()
        },
        createFolder(){

            //Validaciones
            if (!this.folderName){
                this.errors.name = 'El nombre de la carpeta no puede estar vacío'
            }else{
                this.isFloatingBoxDisplayed = false

                axios.post('/api/documents/createFolder', { name: this.folderName, parentFolder: this.currentFolderKey, userSubdomain: this.userSubdomain })
                    .then((res) => {
                        //borro el contenido que tengo
                        this.folderContent = '';

                        //borro el nombre puesto de carpeta
                        this.folderName = ''

                        //listo otra vez
                        this.fetchFolder()
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            }
        },
        openUpdateWindow(fileOrFolder){
            this.isFloatingBoxDisplayed = true;

            this.fileOrFolderToUpdate = fileOrFolder;

            //asigno el nombre de la carpeta que ya tiene
            this.folderName = this.fileOrFolderToUpdate.name;
        },
        updateFileOrFolder(){

            //Validaciones
            if (!this.folderName){
                this.errors.name = 'El nombre no puede estar vacío'
            }else if (this.folderName === this.fileOrFolderToUpdate.name){
                this.errors.name = 'Cambia el nombre ya existente'
            }else{
                this.isFloatingBoxDisplayed = false

                axios.put('/api/documents/updateFileOrFolder', { id: this.fileOrFolderToUpdate.id, name: this.folderName, userSubdomain: this.userSubdomain })
                    .then((res) => {
                        //cierro la ventana
                        this.isFloatingBoxDisplayed = false;

                        //pongo el nombre en local para no actualizar
                        this.fileOrFolderToUpdate.name = this.folderName;

                        //borro el nombre puesto de carpeta
                        this.folderName = ''

                        //quito el archivo/carpeta de la variable
                        this.fileOrFolderToUpdate = '';
                    })
                    .catch((err) => {
                        console.log(err)
                    })
            }
        },
        deleteFileOrFolder(folder){
            Swal.fire({
                icon: 'warning',
                title: '¡Ten cuidado!',
                text: 'Si continuas con esta acción no podrás deshacerla',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                showConfirmButton: true,
                confirmButtonText: 'Borrar',
                confirmButtonColor: 'red',
            }).then((res) => {
                if (res.isConfirmed)
                    axios.post(`/api/documents/deleteFileOrFolder`, {id: folder.id, userSubdomain: this.userSubdomain})
                        .then((res) => {

                            //borro el contenido que tengo
                            this.folderContent = '';

                            //listo otra vez
                            this.fetchFolder()
                        })
                        .catch((err) => {
                            let errText = JSON.parse(err.response.data.message)

                            errText = errText['error']['errors'][0]['message']

                            Swal.fire({
                                icon: 'error',
                                title: '¡Ha occurrido un problema!',
                                text: errText
                            })
                        })
            })
        },
        cancelFolder(){
            this.folderName = ''
            this.isFloatingBoxDisplayed = false
        },
        changeFolder(folder){

            console.log('path',this.path)

            //añado al path
            this.path.push({
                id: folder.id,
                name: folder.name
            })

            //Cambio la id de la carpeta que estoy viendo
            this.currentFolderKey = folder.id;

            //Listo la carpeta
            this.fetchFolder()
        },
        changeFolderFromRoute(folder){

            //Aqui se llega cuando se va a cambiar de ruta pero desde el path, asi que tengo que coger el array path y sacar todas las carpetas que hayan hasta la que
            // se ha seleccionado
            let index = this.path.findIndex(folderNow => folderNow.name === folder.name);

            this.path = this.path.slice(0, index + 1);

            //Cambio la id de la carpeta que estoy viendo
            this.currentFolderKey = folder.id;

            //Listo la carpeta
            this.fetchFolder()
        },
        createFile(){

            let data = new FormData();

            this.files.forEach((file, fileInd) => {
                data.append('files[]', file);
            })
            data.append('parentFolder', this.currentFolderKey)

            data.append('userSubdomain', JSON.stringify(this.userSubdomain))


            axios.post(`/api/documents/createFile`, data )
                .then((res) => {
                    //borro el contenido que tengo
                    this.folderContent = '';

                    //borro el nombre puesto de carpeta
                    this.file = {
                        name: '',
                        body: ''
                    }

                    //listo otra vez
                    this.fetchFolder()
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        previewFile(file){
            window.open(`https://drive.google.com/file/d/${file.id}/view`, '_blank');
        },
        downloadFile(file){
            axios({
                    url: '/api/documents/downloadFile',
                    method: 'POST',
                    responseType: 'blob',
                    data:{
                            id: file.id,
                            userSubdomain: this.userSubdomain
                    }
            })
                .then((res) => {

                    const url = URL.createObjectURL(res.data);
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', file.name);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                })
                .catch((err) => {
                    console.log(err)
                })
        },
        /*downloadFile(file){

            console.log('file --> ',file)

            let blob = new Blob([file.body], { type: 'image/jpeg' });

            console.log('blob --> ',blob)

            // Crea un enlace <a> para descargar el archivo
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = file.name;
            document.body.appendChild(link);
            link.click();
            link.parentNode.removeChild(link);

        },*/
        getIcon(name){

            let icon = '';

            icon = this.$storage.FILE_ICONS.find((iconType) => {

                return iconType.types.includes(name)
            })

            if (!icon || icon === '')
                return 'fa-file-lines'
            else
                return icon.icon
        },
        openDialog(){

            //borro los archivos que habia antes
            this.files = []

            $('#file').click();
        },
        pickupFiles(){
            this.loading = true;

            let input = $('#file');
            if (input.prop('files')) {
                let files = input.prop('files');

                for (let file of files) {
                    this.files.push(file);
                }
            }

            //Guardamos el archivo
            this.createFile()
        },
        selectOrderType(orderType){
            //Recorro los tipos de ordenacion y los pongo todos a false menos el que se ha seleccionado
            this.filters.radio.sortBy.checked = orderType.value;
        },
        seeFilters(type){

            //Cerrar selects
            this.hideCustomSelects()

            switch(type){
                case 'sort':
                    this.isSeeingFiltersPc.sort  = true;
                    break;
            }
        },
        hideCustomSelects(){
            //Cierro todos los filtros
            this.isSeeingFiltersPc = {
                sort: false
            }
        },
        getPrettyDate(date){

            let dateToShow = new Date(date);

            let year = dateToShow.getFullYear().toString().slice(-2); // Obtener los dos últimos dígitos del año
            let month = this.padNumber(dateToShow.getMonth() + 1); // Sumar 1 al mes, ya que los meses en JavaScript van de 0 a 11
            let day = this.padNumber(dateToShow.getDate());
            let hours = this.padNumber(dateToShow.getHours());
            let minutes = this.padNumber(dateToShow.getMinutes());
            let seconds = this.padNumber(dateToShow.getSeconds());

            return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ':' + seconds;
        },
        padNumber(number){
            return (number < 10 ? '0' : '') + number;
        },
        resetSearch(){

            this.searchFileText = ''
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
        }
        ,
    },
    computed:{
        filteredContent() {
            if (!this.folderContent || this.folderContent.length === 0) return [];

            let contentFiltered = [];
            let folderContent = JSON.parse(JSON.stringify(this.folderContent));

            // Filtro
                for (let key in folderContent) {
                let folderOrContent = folderContent[key];
                let folderOrContentSearch = [folderOrContent.name].join('').replace(' ', '').toLowerCase();

                // Compruebo que la carpeta la puede ver el usuario actual
                let foundFolder = this.privateFolders.find(folder => folder.folderId === folderOrContent.id);
                let canSee = true;

                if (foundFolder) {
                    canSee = false;
                    this.privateFolders.forEach((folder) => {
                        if (folder.folderId === folderOrContent.id) {
                            canSee = folder.users.some((user) => {
                                return user === this.basicData.userLogged.email;
                            });
                        }
                    });
                }

                if (folderOrContentSearch.includes(this.searchFileText.replace(' ', '').toLowerCase()) && canSee) {
                    contentFiltered.push(folderOrContent);
                }
            }

            // Ordenar
            if (contentFiltered.length > 0) {
                // Separar carpetas y archivos
                let folders = contentFiltered.filter(item => item.type === 'carpeta');
                let files = contentFiltered.filter(item => item.type !== 'carpeta');

                // Ordenar carpetas
                folders = folders.sort((a, b) => {
                    switch (this.filters.radio.sortBy.checked) {
                        case 0: // por nombre (A-Z)
                            if (a.name < b.name) return -1;
                            if (a.name > b.name) return 1;
                            return 0;
                        case 1: // por nombre (Z-A)
                            if (a.name < b.name) return 1;
                            if (a.name > b.name) return -1;
                            return 0;
                        case 2: // más antiguo
                            let aDate = new Date(a.lastModifiedTime);
                            let bDate = new Date(b.lastModifiedTime);
                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;
                            return 0;
                        default: // más reciente
                            let aDateRecent = new Date(a.lastModifiedTime);
                            let bDateRecent = new Date(b.lastModifiedTime);
                            if (aDateRecent < bDateRecent) return 1;
                            if (aDateRecent > bDateRecent) return -1;
                            return 0;
                    }
                });

                // Ordenar archivos
                files = files.sort((a, b) => {
                    switch (this.filters.radio.sortBy.checked) {
                        case 0: // por nombre (A-Z)
                            if (a.name < b.name) return -1;
                            if (a.name > b.name) return 1;
                            return 0;
                        case 1: // por nombre (Z-A)
                            if (a.name < b.name) return 1;
                            if (a.name > b.name) return -1;
                            return 0;
                        case 2: // más antiguo
                            let aDate = new Date(a.lastModifiedTime);
                            let bDate = new Date(b.lastModifiedTime);
                            if (aDate < bDate) return -1;
                            if (aDate > bDate) return 1;
                            return 0;
                        default: // más reciente
                            let aDateRecent = new Date(a.lastModifiedTime);
                            let bDateRecent = new Date(b.lastModifiedTime);
                            if (aDateRecent < bDateRecent) return 1;
                            if (aDateRecent > bDateRecent) return -1;
                            return 0;
                    }
                });

                // Combinar carpetas y archivos
                contentFiltered = folders.concat(files);
            }

            return contentFiltered;
        },
        orderTypeSelected(){
            return this.filters.radio.sortBy.data[this.filters.radio.sortBy.checked]
        },
        isDownZoco(){
            //Compruebo si esta debajo de Zoco

            let downZoco = false;
            let userNowToSee = this.basicData.userLogged;

            do{

                if (!!userNowToSee && userNowToSee.responsibles && userNowToSee.responsibles.length > 0){

                    userNowToSee = this.basicData.userListComplete.find((user) => user._id === userNowToSee.responsibles[0])

                    if (userNowToSee.responsibles[0] === '65cb57489c2c285441086a43') downZoco = true
                }

            }while(!downZoco && !!userNowToSee.responsibles && userNowToSee.responsibles.length !== 0)

            return downZoco
        },
        canSeeZoco(){
            //Compruebo si el usuario subdominio tiene marcado que se pueda ver o no
            return this.basicData.userSubdomain.agentsCanSeeZoco;
        }
    }
}
</script>

<style scoped>

</style>
