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

        /* Preset chip editors */
        document.querySelectorAll('.chefpress-preset-editor').forEach(function (box) {
            var field = box.getAttribute('data-field');
            if (!field) return;

            var chips = box.querySelector('.chefpress-preset-chips');
            var input = box.querySelector('.chefpress-preset-input');
            var addBtnEl = box.querySelector('.chefpress-preset-add-btn');

            function addValue(raw) {
                var value = String(raw || '').trim();
                if (!value) {
                    return;
                }

                var normalized = value.toLowerCase();
                var duplicate = false;

                chips.querySelectorAll('input[type="hidden"]').forEach(function (hidden) {
                    if (hidden.value.toLowerCase() === normalized) {
                        duplicate = true;
                    }
                });

                if (duplicate) {
                    return;
                }

                var chip = document.createElement('span');
                chip.className = 'chefpress-preset-chip';
                chip.setAttribute('role', 'listitem');
                chip.textContent = value;

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'chefpress-preset-chip__x';
                removeBtn.setAttribute('aria-label', 'Remove');
                removeBtn.textContent = '×';

                var hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'chefpress_settings[' + field + '][]';
                hiddenInput.value = value;

                removeBtn.addEventListener('click', function () {
                    chip.remove();
                });

                chip.appendChild(removeBtn);
                chip.appendChild(hiddenInput);
                chips.appendChild(chip);

                if (input) {
                    input.value = '';
                    input.focus();
                }
            }

            if (addBtnEl) {
                addBtnEl.addEventListener('click', function () {
                    addValue(input && input.value);
                });
            }

            if (input) {
                input.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter') {
                        ev.preventDefault();
                        addValue(input.value);
                    }
                });
            }

            box.querySelectorAll('.chefpress-preset-chip__x').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var chip = btn.closest('.chefpress-preset-chip');
                    if (chip) {
                        chip.remove();
                    }
                });
            });
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
