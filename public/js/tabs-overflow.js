/* ==========================================================================
   ONGLETS — menu « Plus ▾ » pour ce qui ne tient pas sur une ligne
   --------------------------------------------------------------------------
   Chargé en permanence (plugin TabsOverflow de config/adminlte.php).
   S'applique à toute barre d'onglets en tête de carte (.card-header > .nav) :
   les onglets qui dépassent la largeur passent dans un menu déroulant en bout
   de ligne, recalculé à chaque changement de largeur.

   Les liens sont DÉPLACÉS, pas recopiés : les gestionnaires posés par les vues
   (clic, data-bs-toggle="tab", switchtabNav.js) continuent de fonctionner, et la
   classe nav-link est conservée pour les scripts qui la cherchent. Bootstrap 5
   gère nativement un onglet logé dans un .dropdown-menu.

   Sans JavaScript, la barre retombe sur le comportement de mobile.css
   (une ligne qui défile sous 768 px, retour à la ligne au-dessus).
   ========================================================================== */
(function () {
    'use strict';

    var MIN_TABS = 4;
    var MORE_LABEL = (document.documentElement.lang || 'fr').indexOf('fr') === 0 ? 'Plus' : 'More';

    function enhance(nav) {
        if (nav.dataset.tabsOverflow) return;
        if (nav.classList.contains('flex-column')) return;
        if (nav.querySelector('.dropdown, .dropdown-menu')) return;

        var items = Array.prototype.filter.call(nav.children, function (li) {
            return li.classList.contains('nav-item') && li.querySelector(':scope > .nav-link');
        });
        if (items.length < MIN_TABS) return;

        nav.dataset.tabsOverflow = '1';
        nav.classList.add('nav-overflow');

        var entries = items.map(function (li) {
            return { li: li, link: li.querySelector(':scope > .nav-link') };
        });

        var more = document.createElement('li');
        more.className = 'nav-item dropdown nav-overflow__more';
        more.innerHTML =
            '<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button"' +
            ' aria-haspopup="true" aria-expanded="false"></a>' +
            '<div class="dropdown-menu dropdown-menu-end"></div>';
        nav.appendChild(more);

        var toggle = more.firstElementChild;
        var menu = more.lastElementChild;

        // Libellé du bouton : « Plus », ou le nom de l'onglet actif s'il est dans le menu
        function syncToggle() {
            var active = menu.querySelector('.nav-link.active');
            toggle.textContent = active ? active.textContent.trim() : MORE_LABEL;
            toggle.classList.toggle('active', !!active);
        }

        function fits() {
            var navRect = nav.getBoundingClientRect();
            var last = more.style.display === 'none' ? lastVisible() : more;
            return !last || last.getBoundingClientRect().right <= navRect.right + 0.5;
        }

        function lastVisible() {
            for (var i = entries.length - 1; i >= 0; i--) {
                if (entries[i].li.style.display !== 'none') return entries[i].li;
            }
            return null;
        }

        function layout() {
            if (!nav.clientWidth) return; // onglets masqués : on recalculera à l'affichage

            // 1. Tout remettre dans la barre
            entries.forEach(function (e) {
                if (e.link.parentNode !== e.li) {
                    e.link.classList.remove('dropdown-item');
                    e.li.appendChild(e.link);
                }
                e.li.style.display = '';
            });
            more.style.display = 'none';
            syncToggle();
            if (fits()) return;

            // 2. Basculer dans le menu depuis la fin jusqu'à ce que ça tienne
            more.style.display = '';
            for (var i = entries.length - 1; i > 0; i--) {
                var e = entries[i];
                e.link.classList.add('dropdown-item');
                menu.insertBefore(e.link, menu.firstChild);
                e.li.style.display = 'none';
                syncToggle();
                if (fits()) break;
            }
        }

        var pending = false;
        function schedule() {
            if (pending) return;
            pending = true;
            window.requestAnimationFrame(function () {
                pending = false;
                layout();
            });
        }

        // Changement d'onglet : le libellé du bouton change, donc sa largeur aussi
        nav.addEventListener('click', function (event) {
            if (toggle.contains(event.target)) return; // simple ouverture du menu
            setTimeout(schedule, 0);
        });
        // Bootstrap 5 émet l'événement en natif ; il remonte depuis le lien.
        nav.addEventListener('shown.bs.tab', schedule);

        if ('ResizeObserver' in window) {
            new ResizeObserver(schedule).observe(nav);
        } else {
            window.addEventListener('resize', schedule);
        }
        schedule();
    }

    function init() {
        document.querySelectorAll('.card-header > .nav').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        // Après les scripts de vue (switchtabNav.js…) qui posent l'onglet actif
        document.addEventListener('DOMContentLoaded', function () { setTimeout(init, 0); });
    } else {
        setTimeout(init, 0);
    }
})();
