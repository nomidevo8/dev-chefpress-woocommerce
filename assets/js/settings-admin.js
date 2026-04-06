/**
 * ChefPress — global settings screen (nutrition schema + preset lists).
 */
(function () {
    'use strict';

    function reindexNutritionRows() {
        var tbody = document.getElementById('chefpress-nutrition-rows');
        if (!tbody) return;
        var rows = tbody.querySelectorAll('.chefpress-nutr-row');
        rows.forEach(function (tr, idx) {
            tr.querySelectorAll('input[name*="[nutrition]"]').forEach(function (inp) {
                inp.name = inp.name.replace(/\[nutrition\]\[\d+\]/, '[nutrition][' + idx + ']');
            });
        });
    }

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        var addBtn = document.getElementById('chefpress-add-nutrition-row');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var tmpl = document.getElementById('tmpl-chefpress-nutrition-row');
                if (!tmpl) return;
                var tbody = document.getElementById('chefpress-nutrition-rows');
                if (!tbody) return;
                var idx = tbody.querySelectorAll('.chefpress-nutr-row').length;
                var html = tmpl.innerHTML.replace(/\{\{IDX\}\}/g, String(idx));
                tbody.insertAdjacentHTML('beforeend', html);
                reindexNutritionRows();
            });
        }

        document.addEventListener('click', function (e) {
            var rm = e.target.closest('.chefpress-remove-row');
            if (!rm) return;
            var tr = rm.closest('tr');
            if (tr && tr.parentNode) {
                tr.parentNode.removeChild(tr);
                reindexNutritionRows();
            }
        });

        /* Sidebar Navigation */
        document.querySelectorAll('.chefpress-nav__link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var sectionId = this.getAttribute('data-section');
                if (!sectionId) return;

                // Remove active class from all links
                document.querySelectorAll('.chefpress-nav__link').forEach(function (l) {
                    l.classList.remove('chefpress-nav__link--active');
                });

                // Add active class to clicked link
                this.classList.add('chefpress-nav__link--active');

                // Hide all sections
                document.querySelectorAll('.chefpress-section').forEach(function (section) {
                    section.classList.remove('chefpress-section--active');
                });

                // Show target section
                var targetSection = document.getElementById('chefpress-section-' + sectionId);
                if (targetSection) {
                    targetSection.classList.add('chefpress-section--active');
                }

                // Update URL hash without triggering scroll
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, null, '#' + sectionId);
                }
            });
        });

        /* Handle initial section based on URL hash */
        var hash = window.location.hash.substring(1);
        if (hash) {
            var initialLink = document.querySelector('.chefpress-nav__link[data-section="' + hash + '"]');
            if (initialLink) {
                initialLink.click();
            }
        } else {
            /* Activate first section by default */
            var firstLink = document.querySelector('.chefpress-nav__link');
            if (firstLink) {
                firstLink.click();
            }
        }
    });
})();
