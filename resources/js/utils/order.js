import moment from 'moment';

/**
 * Devuelve la fecha "efectiva" del contrato
 * según la prioridad:
 * transferDate > processingDate > activationDate > sin fecha
 */
export function getOrderDate(order) {

    // Fecha de activación
    if (order.activationDate) {
        return moment(order.activationDate);
    }

    // Fecha de tramitación
    if (order.processingDate) {
        return moment(order.processingDate);
    }

    // Fecha de transferencia
    if (order.transferDate) {
        return moment(order.transferDate, 'DD/MM/YY');
    }

    // Sin fecha: usando moment para devolver una fecha especial, por ejemplo, el 1 de enero de 1970
    return moment('2000-01-01');
}

/**
 * Devuelve el contrato actual de un grupo (mismo CUPS)
 * comparando SOLO por la fecha efectiva
 */
export function getCurrentOrderByCups(orders = []) {
    if (!Array.isArray(orders) || orders.length === 0) return null;

    return orders
        .slice()
        .sort((a, b) => getOrderDate(b) - getOrderDate(a))[0];
}
