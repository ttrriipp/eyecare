{{-- Default appearance is light (not OS). "System" is stored via flux.appearance.system so it is distinct from no preference. Synced with Flux Alpine $flux.appearance. --}}
<style>
    :root.dark {
        color-scheme: dark;
    }
</style>
<script>
    window.Flux = {
        applyAppearance(appearance) {
            let applyDark = () => document.documentElement.classList.add('dark');
            let applyLight = () => document.documentElement.classList.remove('dark');

            if (appearance === 'system') {
                let media = window.matchMedia('(prefers-color-scheme: dark)');

                window.localStorage.removeItem('flux.appearance');
                window.localStorage.setItem('flux.appearance.system', '1');

                media.matches ? applyDark() : applyLight();
            } else if (appearance === 'dark') {
                window.localStorage.setItem('flux.appearance', 'dark');
                window.localStorage.removeItem('flux.appearance.system');

                applyDark();
            } else if (appearance === 'light') {
                window.localStorage.setItem('flux.appearance', 'light');
                window.localStorage.removeItem('flux.appearance.system');

                applyLight();
            }
        },
    };

    (function () {
        var stored = window.localStorage.getItem('flux.appearance');
        var systemChosen = window.localStorage.getItem('flux.appearance.system') === '1';
        var initial = 'light';
        if (stored === 'light' || stored === 'dark') {
            initial = stored;
        } else if (systemChosen) {
            initial = 'system';
        }
        window.Flux.applyAppearance(initial);
    })();
</script>
