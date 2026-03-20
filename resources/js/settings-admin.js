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
                var val = String(raw || '').trim();
                if (!val) return;
                var dup = false;
                chips.querySelectorAll('input[type="hidden"]').forEach(function (h) {
                    if (String(h.value).toLowerCase() === val.toLowerCase()) dup = true;
                });
                if (dup) return;

                var chip = document.createElement('span');
                chip.className = 'chefpress-preset-chip';
                chip.setAttribute('role', 'listitem');
                chip.appendChild(document.createTextNode(val));

                var x = document.createElement('button');
                x.type = 'button';
                x.className = 'chefpress-preset-chip__x';
                x.setAttribute('aria-label', 'Remove');
                x.innerHTML = '×';

                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'chefpress_settings[' + field + '][]';
                hidden.value = val;

                chip.appendChild(x);
                chip.appendChild(hidden);
                chips.appendChild(chip);

                x.addEventListener('click', function () {
                    chip.remove();
                });

                if (input) input.value = '';
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

            box.querySelectorAll('.chefpress-preset-chip__x').forEach(function (bx) {
                bx.addEventListener('click', function () {
                    var c = bx.closest('.chefpress-preset-chip');
                    if (c) c.remove();
                });
            });
        });
    });
})();
