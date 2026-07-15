import * as $storage from "./storage";

//Sacar usuario subdominio a partir de un usuario
export function obtainSubdomainUser(userId, userList){

    //Saco el usuario principal
    let user = userList.find((userNow) => userNow._id === userId)

    if (!user) return null

    if (user.label === 'Usuario subdominio') return user

    //Busco el usuario por encima hasta llegar a subdominio
    do {
        if (!Array.isArray(user.responsibles) || !user.responsibles[0]) return null
        user = userList.find((userNow) => userNow._id  === user.responsibles[0])
        if (!user) return null
    }while(user.label !== 'Usuario subdominio')

    return user
}


//función para obtener el usuario por debajo del subdominio del que cuelga un usuario
export function obtainUserDownSubdomain(userId, userList, userSubdomainId){

    //Saco el usuario principal
    let user = userList.find((userNow) => userNow._id === userId)

    if (!user) return null

    if (user.label === "Usuario subdominio") return user

    if (!Array.isArray(user.responsibles)) return null

    if (user.responsibles.includes(userSubdomainId)) return user

    //Busco el usuario por encima hasta llegar a subdominio
    do {
        if (!Array.isArray(user.responsibles) || !user.responsibles[0]) return null
        user = userList.find((userNow) => userNow._id  === user.responsibles[0])
        if (!user || !Array.isArray(user.responsibles)) return null
    }while(!user.responsibles.includes(userSubdomainId))

    return user
}


/**
 * Función para validar la comparativa de los totales de la factura y los datos de CUPS.
 * @param {number} billTotal - El total calculado de la factura.
 * @param {number} cupsTotal - El total calculado de los datos de CUPS.
 * @param {number} marginOfError - El margen de error permitido (porcentaje, e.g., 0.05 para 5%).
 * @returns {boolean} - Retorna true si la comparativa es válida, false si no lo es.
 */
export function validateComparison(billTotal, cupsTotal, marginOfError = 0.1) {
    const difference = Math.abs(billTotal - cupsTotal);
    const percentageDifference = difference / billTotal;

    // Verifica si la diferencia está dentro del margen permitido
    return percentageDifference <= marginOfError;
}
