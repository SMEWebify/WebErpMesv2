    document.addEventListener('DOMContentLoaded', function () {
        const hash = window.location.hash;
        const tabs = document.querySelectorAll('#DocumentTabs .nav-link');
        const tabContents = document.querySelectorAll('.tab-pane');

        // Si un fragment est présent dans l'URL
        if (hash) {
        // Cacher tous les contenus des onglets
        tabContents.forEach(tabContent => {
            tabContent.classList.remove('active');
        });

        // Afficher le contenu de l'onglet correspondant au fragment
        const activeTabContent = document.querySelector(hash);
        if (activeTabContent) {
            activeTabContent.classList.add('active');
        }

        // Activer le lien de l'onglet correspondant
        tabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.getAttribute('href') === hash) {
            tab.classList.add('active');
            }
        });
        } else {
        // Si aucun fragment, activer le premier onglet par défaut
        const firstTab = tabs[0]; // Sélectionne le premier onglet
        if (firstTab) {
            firstTab.classList.add('active');
            document.querySelector(firstTab.getAttribute('href')).classList.add('active');
        }
        }
    });