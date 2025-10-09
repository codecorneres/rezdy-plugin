const rezdyForms = {
    timeoutId: null,
    sent: false,

    init: function () {
        this.listenToGForm6()
    },

    listenToGForm6: function () {
        const sendAbandonedCart = () => {
            const forms = [
                {
                    selector: 'form#gform_4',
                    requiredInputs: ['#input_6_3', '#input_6_5', '#input_6_6']
                },
                {
                    selector: 'form#gform_6',
                    requiredInputs: ['#input_6_3', '#input_6_5', '#input_6_6']
                }
            ]

            const allFormsValid = forms.some(({ selector, requiredInputs }) => {
                const form = document.querySelector(selector)
                if (form) {
                    return requiredInputs.every((inputId) => {
                        const input = form.querySelector(inputId)
                        return input && input.value.trim() !== ''
                    })
                }
            })

            if (! allFormsValid) return

            if (this.sent) return
            this.sent = true

            const formIds = ['form#gform_4', 'form#gform_6']
            formIds.forEach((formId) => {
                const form = document.querySelector(formId)
                if (form) {
                    const formData = new FormData(form)
                    formData.append('action', 'rezdy_gravity_forms_abandoned')

                    const params = new URLSearchParams()
                    for (const [key, value] of formData.entries()) {
                        const newKey = key.startsWith('input_') ? key.replace('input_', '') : key
                        params.append(newKey, value)
                    }
                    params.append('form_action_type', 'Abandoned cart')

                    navigator.sendBeacon(rezdyFormsData.ajaxUrl, params)
                }
            })
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                this.timeoutId = setTimeout(sendAbandonedCart, 15000)
            } else {
                clearTimeout(this.timeoutId)
            }
        })

        window.addEventListener('unload', sendAbandonedCart)
    }
}

const rezdyWpForms = {
    timeoutId: null,
    sent: false,

    init: function () {
        this.listenWpForms()
    },

    listenWpForms: function () {
        const sendAbandonedCart = () => {
            const forms = [
                {
                    selector: 'form#wpforms-form-5990',
                    requiredInputs: ['#wpforms-5990-field_0', '#wpforms-5990-field_1']
                },
                {
                    selector: 'form#wpforms-form-7865',
                    requiredInputs: ['#wpforms-7865-field_0', '#wpforms-7865-field_1']
                }
            ]

            const allFormsValid = forms.some(({ selector, requiredInputs }) => {
                const form = document.querySelector(selector)
                if (form) {
                    return requiredInputs.every((inputId) => {
                        const input = form.querySelector(inputId)
                        return input && input.value.trim() !== ''
                    })
                }
            })

            if (! allFormsValid) return

            if (this.sent) return
            this.sent = true

            const formIds = ['form#wpforms-form-5990', 'form#wpforms-form-7865']
            formIds.forEach((formId) => {
                const form = document.querySelector(formId)
                if (form) {
                    const formData = new FormData(form)
                    formData.append('action', 'rezdy_wp_forms_abandoned')

                    const params = new URLSearchParams()
                    for (const [key, value] of formData.entries()) {
                        const newKey = key.startsWith('input_') ? key.replace('input_', '') : key
                        params.append(newKey, value)
                    }
                    params.append('form_action_type', 'Abandoned cart')

                    navigator.sendBeacon(rezdyFormsData.ajaxUrl, params)
                }
            })
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                this.timeoutId = setTimeout(sendAbandonedCart, 15000)
            } else {
                clearTimeout(this.timeoutId)
            }
        })

        window.addEventListener('unload', sendAbandonedCart)
    }
}

document.addEventListener('DOMContentLoaded', () => {
    rezdyForms.init()
    rezdyWpForms.init()
})
