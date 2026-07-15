import { reactive } from 'vue'

const callState = reactive({
    active: false,
    phone: '',
    name: '',
    id: '',
    isOrder: true,
    enterpriseId: ''
})

export function startCall(phone, name, id, isOrder, enterpriseId) {
    callState.phone = phone
    callState.name = name
    callState.id = id
    callState.isOrder = isOrder
    callState.active = true
    callState.enterpriseId = enterpriseId
}

export function endCall() {
    callState.active = false
    callState.phone = ''
    callState.name = ''
    callState.isOrder = true
    callState.id = ''
    callState.enterpriseId = ''
}

export function useCallState() {
    return callState
}
