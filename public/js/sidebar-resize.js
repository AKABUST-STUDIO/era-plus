(function () {
    const stored = parseInt(window.localStorage.getItem('sidebarWidth'))

    if (stored) {
        document.documentElement.style.setProperty('--sidebar-width', stored + 'px')
    }
})()

document.addEventListener('alpine:init', () => {
    window.Alpine.data('sidebarResize', () => ({
        width: parseInt(window.localStorage.getItem('sidebarWidth')) || null,
        isResizing: false,

        init() {
            this.applyWidth()
        },

        applyWidth() {
            if (this.width) {
                document.documentElement.style.setProperty('--sidebar-width', this.width + 'px')
            }
        },

        startResize() {
            this.isResizing = true
            document.body.style.cursor = 'col-resize'
        },

        doResize(event) {
            if (! this.isResizing) {
                return
            }

            const rect = this.$el.getBoundingClientRect()
            const isRtl = window.getComputedStyle(this.$el).direction === 'rtl'
            const raw = isRtl ? rect.right - event.clientX : event.clientX - rect.left

            if (raw < 120) {
                this.stopResize()
                this.$store.sidebar.close()

                return
            }

            this.width = Math.min(480, Math.max(180, Math.round(raw)))
            this.applyWidth()
        },

        stopResize() {
            if (! this.isResizing) {
                return
            }

            this.isResizing = false
            document.body.style.cursor = ''
            window.localStorage.setItem('sidebarWidth', this.width)
        },

        resetWidth() {
            this.width = null
            window.localStorage.removeItem('sidebarWidth')
            document.documentElement.style.removeProperty('--sidebar-width')
        },
    }))
})
