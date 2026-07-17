import { Passkeys } from '@laravel/passkeys'

const notifyFailure = (title, error) => {
    new window.FilamentNotification()
        .title(title)
        .body(error?.message)
        .danger()
        .send()
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('passkeySignIn', ({ redirectUrl, failureTitle }) => ({
        busy: false,

        async signIn() {
            if (this.busy) {
                return
            }

            this.busy = true

            try {
                await Passkeys.verify()

                window.location.href = redirectUrl
            } catch (error) {
                notifyFailure(failureTitle, error)
            } finally {
                this.busy = false
            }
        },
    }))

    window.Alpine.data('passkeyRegister', ({ namePrompt, failureTitle }) => ({
        busy: false,

        async register() {
            if (this.busy) {
                return
            }

            const name = window.prompt(namePrompt)

            if (! name) {
                return
            }

            this.busy = true

            try {
                await Passkeys.register({ name })

                this.$wire.$refresh()
            } catch (error) {
                notifyFailure(failureTitle, error)
            } finally {
                this.busy = false
            }
        },
    }))
})
