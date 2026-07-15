export const PERMISSIONS = [
    {
        code: 'RESUS',
        title: 'Registro de usuarios',
        desc: 'Este permiso permite registrar usuarios'
    },
    {
        code: 'GESCON',
        title: 'Gestion contratos',
        desc: 'Este permiso permite editar algunos campos especiales de los pedidos de una cuenta'
    },
    {
        code: 'DRIVE',
        title: 'Gestion Google Drive',
        desc: 'Este permiso permite crear y eliminar carpetas y archivos en el directorio de documentos'
    },
    {
        code: 'READONLY',
        title: 'Solo lectura',
        desc: 'Este permiso hace que el usuario que lo tenga solo pueda visualizar, no modificar nada.'
    },
];

export const LABELS = [
  'Usuario',
  'Usuario demo',
  'Usuario subdominio',
  'Usuario drive',
  'Cliente',

  'Comercial',
  'Comercial Drive',

  'Administrador',
  'Jefe administrador',

  'Desarrollador',
  'Tramitador',

  'Súper usuario',
  "Cliente",
  "Trabajador",
  "Jefe Trabajador"
];

export const LABELS_COLOR = [
    'azul',
    'morado',
    'naranja',
    'verde',
    'rojo',
    'main',
];


//Estados llamada Twilio
export const TWILIO_CALL_STATUSES = [
    {
        code: 'queued',
        title: 'En cola',
        color: 'queuedTwilio'
    },

    {
        code: 'ringing',
        title: 'Llamando',
        color: 'ringingTwilio',
    },

    {
        code: 'in-progress',
        title: 'En progreso',
        color: 'in-progressTwilio'
    },

    {
        code: 'completed',
        title: 'Completada',
        color: 'completedTwilio',
    },

    {
        code: 'busy',
        title: 'Ocupado',
        color: 'busyTwilio'
    },

    {
        code: 'failed',
        title: 'Fallida',
        color: 'failedTwilio'
    },

    {
        code: 'no-answer',
        title: 'Sin respuesta',
        color: 'no-answerTwilio'
    },

    {
        code: 'canceled',
        title: 'Cancelada',
        color: 'canceledTwilio'
    },
];


export const FILE_ICONS =[
    {
        types: ['application/vnd.google-apps.folder', 'carpeta'],
        icon: 'fas fa-folder'
    },
    {
        types: ['image/png', 'image/jpeg', 'image/gif', 'image/bmp', 'image/vnd.microsoft.icon', 'image/tiff'],
        icon: 'far fa-image'
    },
    {
        types: ['image/vnd.adobe.photoshop', 'application/x-photoshop', 'application/postscript', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        icon: 'far fa-file'
    },
    {
        types: ['application/pdf'],
        icon: 'far fa-file-pdf'
    },
    {
        types: ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        icon: 'far fa-file-spreadsheet'
    },
    {
        types: ['audio/midi', 'audio/mpeg', 'audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3', 'audio/x-aiff', 'audio/x-pn-realaudio', 'audio/x-pn-realaudio-plugin',
            'audio/x-realaudio', 'video/vnd.rn-realvideo','audio/x-wav', 'audio/wave', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/x-sgi-movie'],
        icon: 'far fa-video'
    },
    {
        types: ['application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed', 'application/x-msdownload', 'application/vnd.ms-cab-compressed'],
        icon: 'far fa-file-zipper'
    }
];


export const FEES = {
    electricity: [
        'Tarifa 2.0TD',
        'Tarifa 3.0TD',
        'Tarifa 6.1TD'
    ],
    gas: [
        'Tarifa RL1',
        'Tarifa RL2',
        'Tarifa RL3',
        'Tarifa RL4',
        'Tarifa RL5',
        'Tarifa RL6',
    ],
    telephony: [
        'Fibra + movil',
        'Fibra',
        'Movil'
    ],
    selfSupply: [
        'Baterias Península, Ceuta y Melilla',
        'Residencial Ceuta y Melilla',
        'Residencial Península',
        'Industrial Ceuta y Melilla',
        'Industrial Península',
        'General'
    ]
}



export const PRODUCT_TYPES = [
    {
        code: 'cl',
        title: 'Contrato de luz',
        inDatabase: 'electricity',
        verificationsAvailable: ['nw', 'pc', 'tc', 'vb', 'mc', 're']
    },
    {
        code: 'cg',
        title: 'Contrato de gas',
        inDatabase: 'gas',
        verificationsAvailable: ['nw', 'pc', 'tc', 'vb', 'mc', 're']
    },
    {
        code: 'cd',
        title: 'Contrato dual',
        inDatabase: 'dual',
        verificationsAvailable: ['nw', 'pc', 'tc', 'vb', 'mc', 're']
    },
    {
        code: 'ct',
        title: 'Contrato de telefonía',
        inDatabase: 'telephony',
        verificationsAvailable: ['nw', 'tc']
    },
    {
        code: 'sa',
        title: 'Servicio de alarmas',
        inDatabase: 'alarm',
        verificationsAvailable: []
    },
    {
        code: 'a',
        title: 'Autoconsumo',
        inDatabase: 'selfSupply',
        verificationsAvailable: []
    },
    {
        code: 'bc',
        title: 'Bateria de condensadores',
        productToSee: 'n',
        verificationsAvailable: []
    },
    {
        code: 'ce',
        title: 'Coche eléctrico',
        productToSee: 'electricCar',
        verificationsAvailable: []
    },
    {
        code: 'c',
        title: 'Contador',
        productToSee: 'electricityMeter',
        verificationsAvailable: []
    },
    {
        code: 'i',
        title: 'Iluminación',
        productToSee: 'n',
        verificationsAvailable: []
    },
    {
        code: 'crm',
        title: 'Servicios CRM',
        productToSee: 'crm',
        verificationsAvailable: []
    },
    {
        code: 'sp',
        title: 'Sin producto',
        productToSee: 'sp',
        verificationsAvailable: []
    },
]
