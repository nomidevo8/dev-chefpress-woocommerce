/**
 * Dev ChefPress for WooCommerce — Admin JS
 * Handles: tabs, collapsible cards, drag-drop repeaters, nested ingredients,
 *          media uploader, tag input, star preview.
 */
/* global ChefPressAdmin, Sortable, wp */
(function ($) {
    'use strict';

    /* ==========================================================================
       Helpers
       ========================================================================== */
    function reindexItems($list, itemSelector, fieldFn) {
        $list.children(itemSelector).each(function (newIdx) {
            fieldFn($(this), newIdx);
        });
    }

    function stripTemplate(html) {
        return html
            .replace(/class="[^"]*sortable-ghost[^"]*"/g, '')
            .replace(/class="[^"]*sortable-chosen[^"]*"/g, '');
    }

    /* ==========================================================================
       Sub-Navigation Tabs
       ========================================================================== */
    var TabManager = {
        init: function () {
            $(document).on('click', '.cp-subnav__btn', function () {
                var $btn = $(this);
                var targetId = $btn.data('tab');

                // Update buttons.
                $btn.closest('.cp-subnav').find('.cp-subnav__btn').removeClass('active');
                $btn.addClass('active');

                // Show target panel.
                $btn.closest('#chefpress_recipe_data').find('.cp-tab-content').removeClass('active');
                $('#' + targetId).addClass('active');
            });
        }
    };

    /* ==========================================================================
       Collapsible Card Sections
       ========================================================================== */
    var CardCollapse = {
        init: function () {
            // Start all cards open.
            $('.cp-card__header').addClass('is-open');

            $(document).on('click', '.cp-card__header', function () {
                var $header = $(this);
                var targetId = $header.data('toggle');
                var $body = $('#' + targetId);

                $header.toggleClass('is-open');
                $body.slideToggle(180);
            });
        }
    };

    /* ==========================================================================
       Repeater Item Toggle (accordion inside repeater)
       ========================================================================== */
    var RepeaterToggle = {
        init: function () {
            $(document).on('click', '.cp-btn-toggle', function (e) {
                e.stopPropagation();
                var $item = $(this).closest('.cp-repeater-item');
                var $body = $item.find('> .cp-repeater-item__body');
                $body.toggleClass('is-collapsed').slideToggle(180);
            });

            // Click on header row (not buttons) also toggles.
            $(document).on('click', '.cp-repeater-item__header', function (e) {
                if ($(e.target).closest('.cp-repeater-item__actions, .cp-drag-handle').length) {
                    return;
                }
                var $body = $(this).siblings('.cp-repeater-item__body');
                $body.toggleClass('is-collapsed').slideToggle(180);
            });
        }
    };

    /* ==========================================================================
       Live Preview Title in Repeater Header
       ========================================================================== */
    var RepeaterPreview = {
        init: function () {
            $(document).on('input', '.cp-step-title-input', function () {
                var val = $(this).val().trim() || ChefPressAdmin.strings.noTitle;
                $(this).closest('.cp-repeater-item')
                    .find('> .cp-repeater-item__header .cp-repeater-item__preview-title')
                    .text(val);
            });

            $(document).on('input', '.cp-group-name-input', function () {
                var val = $(this).val().trim() || ChefPressAdmin.strings.noTitle;
                $(this).closest('.cp-repeater-item')
                    .find('> .cp-repeater-item__header .cp-repeater-item__preview-title')
                    .text(val);
            });
        }
    };

    /* ==========================================================================
       Steps Repeater
       ========================================================================== */
    var StepsRepeater = {
        $list: null,
        sortable: null,

        init: function () {
            this.$list = $('#cp-steps-list');
            if (!this.$list.length) return;

            this.initSortable();
            this.bindAdd();
            this.bindRemove();
            this.bindDuplicate();
            this.updateBadges();
        },

        initSortable: function () {
            var self = this;
            this.sortable = new Sortable(this.$list[0], {
                animation: 200,
                handle: '.cp-drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'is-dragging',
                onEnd: function () {
                    self.reindex();
                    self.updateBadges();
                }
            });
        },

        bindAdd: function () {
            var self = this;
            $('#cp-add-step').on('click', function () {
                var newIdx = self.$list.children('.cp-step-item').length;
                var tpl = $('#cp-step-template').html();
                if (!tpl) return;

                tpl = tpl.replace(/\{\{INDEX\}\}/g, newIdx);
                var $newItem = $(tpl);
                $newItem.find('.cp-repeater-item__body').show();
                self.$list.append($newItem);

                // Scroll into view.
                $newItem[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                self.reindex();
                self.updateBadges();

                // Initialize media on new item.
                MediaUploader.bindItem($newItem);
            });
        },

        bindRemove: function () {
            var self = this;
            $(document).on('click', '#cp-steps-list .cp-btn-remove', function (e) {
                e.stopPropagation();
                if (!confirm(ChefPressAdmin.strings.removeConfirm)) return;
                $(this).closest('.cp-step-item').slideUp(200, function () {
                    $(this).remove();
                    self.reindex();
                    self.updateBadges();
                });
            });
        },

        bindDuplicate: function () {
            var self = this;
            $(document).on('click', '#cp-steps-list .cp-btn-duplicate', function (e) {
                e.stopPropagation();
                var $item = $(this).closest('.cp-step-item');
                var $clone = $item.clone(true);
                $clone.find('.cp-repeater-item__body').show().removeClass('is-collapsed');
                $item.after($clone);
                $clone.addClass('cp-flash');
                setTimeout(function () { $clone.removeClass('cp-flash'); }, 700);
                self.reindex();
                self.updateBadges();
                MediaUploader.bindItem($clone);
            });
        },

        /**
         * Remove TinyMCE / wp.editor instance for a step description field.
         */
        removeStepEditor: function (editorId) {
            if (!editorId) return;
            if (window.wp &&
                wp.editor &&
                typeof wp.editor.remove === 'function') {
                try {
                    wp.editor.remove(editorId);
                } catch (ignore) { /* no instance */ }
            }
            if (window.tinymce && tinymce.get(editorId)) {
                try {
                    tinymce.execCommand('mceRemoveEditor', false, editorId);
                } catch (ignore) { /* no instance */ }
            }
        },

        /**
         * Convert wp_editor wrappers to plain textareas so indices / cloning stay reliable.
         */
        normalizeDescriptionsToTextareas: function () {
            if (window.tinymce && typeof tinymce.triggerSave === 'function') {
                tinymce.triggerSave();
            }
            this.$list.children('.cp-step-item').each(function () {
                var $wrap = $(this).find('.wp-editor-wrap');
                if (!$wrap.length) return;

                var $ta = $wrap.find('textarea.cp-step-description-field, textarea.wp-editor-area').first();
                if (!$ta.length) return;

                var id = $ta.attr('id');
                var name = $ta.attr('name');
                var val = $ta.val() || '';

                if (id && window.tinymce && tinymce.get(id)) {
                    val = tinymce.get(id).getContent();
                    try {
                        tinymce.execCommand('mceRemoveEditor', false, id);
                    } catch (ignore) { /* */ }
                } else if (id && window.wp && wp.editor && typeof wp.editor.remove === 'function') {
                    try {
                        wp.editor.remove(id);
                    } catch (ignore) { /* */ }
                    val = $ta.val() || val;
                }

                var $newTa = $('<textarea/>')
                    .addClass('widefat cp-textarea cp-step-description-field')
                    .attr({ rows: 6, name: name || '' })
                    .val(val);
                $wrap.replaceWith($newTa);
            });
        },

        /**
         * Attach visual editor to each step description after reindex / order change.
         */
        initDescriptionEditors: function () {
            if (!window.wp || !wp.editor || typeof wp.editor.initialize !== 'function') return;

            var settings = ChefPressAdmin.stepEditorSettings || {};
            var self = this;

            this.$list.children('.cp-step-item').each(function (idx) {
                var $ta = $(this).find('textarea.cp-step-description-field').first();
                if (!$ta.length) return;

                var editorId = 'chefpress_step_desc_' + idx;
                self.removeStepEditor(editorId);
                $ta.attr('id', editorId);
                wp.editor.initialize(editorId, settings);
            });
        },

        reindex: function () {
            this.normalizeDescriptionsToTextareas();
            this.$list.children('.cp-step-item').each(function (newIdx) {
                $(this).attr('data-index', newIdx);
                $(this).find('[name]').each(function () {
                    var $f = $(this);
                    var name = $f.attr('name');
                    if (name) {
                        $f.attr('name', name.replace(/_chefpress_steps\[\d+\]/, '_chefpress_steps[' + newIdx + ']'));
                    }
                });
            });
            this.initDescriptionEditors();
        },

        updateBadges: function () {
            this.$list.children('.cp-step-item').each(function (idx) {
                $(this).find('> .cp-repeater-item__header .cp-repeater-item__badge')
                    .text(ChefPressAdmin.strings.stepLabel + ' ' + (idx + 1));
            });
        }
    };

    /* ==========================================================================
       Ingredient Groups Repeater (nested)
       ========================================================================== */
    var GroupsRepeater = {
        $list: null,
        sortable: null,

        init: function () {
            this.$list = $('#cp-groups-list');
            if (!this.$list.length) return;

            this.initSortable();
            this.bindAdd();
            this.bindRemove();
            this.bindDuplicate();
            this.updateBadges();

            // Init ingredient sortables for existing groups.
            this.$list.find('.cp-ingredients-list').each(function () {
                GroupsRepeater.initIngredientSortable($(this));
            });
        },

        initSortable: function () {
            var self = this;
            this.sortable = new Sortable(this.$list[0], {
                animation: 200,
                handle: '> .cp-repeater-item__header > .cp-drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'is-dragging',
                onEnd: function () {
                    self.reindex();
                    self.updateBadges();
                }
            });
        },

        initIngredientSortable: function ($ingList) {
            new Sortable($ingList[0], {
                animation: 150,
                handle: '.cp-drag-handle--sm',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'is-dragging',
                onEnd: function () {
                    GroupsRepeater.reindexIngredients($ingList);
                }
            });
        },

        bindAdd: function () {
            var self = this;
            $('#cp-add-group').on('click', function () {
                var newIdx = self.$list.children('.cp-group-item').length;
                var tpl = $('#cp-group-template').html();
                if (!tpl) return;

                tpl = tpl.replace(/\{\{INDEX\}\}/g, newIdx);
                var $newItem = $(tpl);
                $newItem.find('.cp-repeater-item__body').show();
                self.$list.append($newItem);

                $newItem[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Init ingredient sortable on new group.
                self.initIngredientSortable($newItem.find('.cp-ingredients-list'));

                self.reindex();
                self.updateBadges();
            });
        },

        bindRemove: function () {
            var self = this;
            $(document).on('click', '#cp-groups-list .cp-btn-remove', function (e) {
                e.stopPropagation();
                if (!confirm(ChefPressAdmin.strings.removeConfirm)) return;
                $(this).closest('.cp-group-item').slideUp(200, function () {
                    $(this).remove();
                    self.reindex();
                    self.updateBadges();
                });
            });
        },

        bindDuplicate: function () {
            var self = this;
            $(document).on('click', '#cp-groups-list .cp-btn-duplicate', function (e) {
                e.stopPropagation();
                var $item = $(this).closest('.cp-group-item');
                var $clone = $item.clone(true);
                $clone.find('.cp-repeater-item__body').show().removeClass('is-collapsed');
                $item.after($clone);
                $clone.addClass('cp-flash');
                setTimeout(function () { $clone.removeClass('cp-flash'); }, 700);

                // Re-init sortable on cloned ingredient list.
                self.initIngredientSortable($clone.find('.cp-ingredients-list'));

                self.reindex();
                self.updateBadges();
            });
        },

        /* ----- Add Ingredient inside group ----- */
        bindAddIngredient: function () {
            $(document).on('click', '.cp-add-ingredient', function () {
                var $btn = $(this);
                var groupIdx = $btn.data('group-index');
                var $ingList = $btn.siblings('.cp-ingredients-list');
                var newIngIdx = $ingList.children('.cp-ingredient-item').length;

                var tpl = $('#cp-ingredient-template').html();
                if (!tpl) return;

                tpl = tpl.replace(/\{\{GROUP_INDEX\}\}/g, groupIdx);
                tpl = tpl.replace(/\{\{ING_INDEX\}\}/g, newIngIdx);
                var $newIng = $(tpl);
                $ingList.append($newIng);

                GroupsRepeater.reindexIngredients($ingList);
            });
        },

        /* ----- Remove Ingredient ----- */
        bindRemoveIngredient: function () {
            $(document).on('click', '.cp-btn-remove-ingredient', function (e) {
                e.stopPropagation();
                var $item = $(this).closest('.cp-ingredient-item');
                var $ingList = $item.closest('.cp-ingredients-list');
                $item.slideUp(150, function () {
                    $(this).remove();
                    GroupsRepeater.reindexIngredients($ingList);
                });
            });
        },

        reindex: function () {
            var self = this;
            this.$list.children('.cp-group-item').each(function (newGroupIdx) {
                var $group = $(this);
                $group.attr('data-index', newGroupIdx);

                // Update group-level field names.
                $group.find('[name]').each(function () {
                    var $f = $(this);
                    var name = $f.attr('name');
                    if (name) {
                        $f.attr('name', name.replace(/_chefpress_groups\[\d+\]/, '_chefpress_groups[' + newGroupIdx + ']'));
                    }
                });

                // Update the add-ingredient button's group index.
                $group.find('.cp-add-ingredient').attr('data-group-index', newGroupIdx);

                // Reindex ingredients within this group.
                var $ingList = $group.find('.cp-ingredients-list');
                $ingList.attr('data-group-index', newGroupIdx);
                self.reindexIngredients($ingList, newGroupIdx);
            });
        },

        reindexIngredients: function ($ingList, forcedGroupIdx) {
            var groupIdx = forcedGroupIdx !== undefined
                ? forcedGroupIdx
                : $ingList.closest('.cp-group-item').attr('data-index');

            $ingList.children('.cp-ingredient-item').each(function (newIngIdx) {
                $(this).attr('data-ingredient-index', newIngIdx);
                $(this).find('[name]').each(function () {
                    var $f = $(this);
                    var name = $f.attr('name');
                    if (name) {
                        // Replace group index.
                        name = name.replace(/_chefpress_groups\[\d+\]/, '_chefpress_groups[' + groupIdx + ']');
                        // Replace ingredient index.
                        name = name.replace(/\[ingredients\]\[\d+\]/, '[ingredients][' + newIngIdx + ']');
                        $f.attr('name', name);
                    }
                });
            });
        },

        updateBadges: function () {
            this.$list.children('.cp-group-item').each(function (idx) {
                $(this).find('> .cp-repeater-item__header .cp-repeater-item__badge')
                    .text(ChefPressAdmin.strings.groupLabel + ' ' + (idx + 1));
            });
        }
    };

    /* ==========================================================================
       WordPress Media Uploader
       ========================================================================== */
    var MediaUploader = {
        init: function () {
            this.bindItem($('#chefpress_recipe_data'));
        },

        bindItem: function ($scope) {
            $scope.find('.cp-btn-media-upload').each(function () {
                MediaUploader.attachUploadButton($(this));
            });

            // Delegate for dynamically-added buttons.
            $(document).off('click.cpMedia', '.cp-btn-media-upload')
                .on('click.cpMedia', '.cp-btn-media-upload', function () {
                    MediaUploader.openMediaFrame($(this));
                });

            $(document).off('click.cpMediaRemove', '.cp-btn-media-remove')
                .on('click.cpMediaRemove', '.cp-btn-media-remove', function () {
                    MediaUploader.removeImage($(this));
                });
        },

        attachUploadButton: function ($btn) {
            // No-op — handled via delegated events.
        },

        openMediaFrame: function ($btn) {
            var $wrap    = $btn.closest('.cp-media-field');
            var $preview = $wrap.find('.cp-media-preview');
            var $idInput = $wrap.find('.cp-media-id');
            var $removeBtn = $wrap.find('.cp-btn-media-remove');

            var frame = wp.media({
                title  : ChefPressAdmin.strings.selectImage,
                button : { text: ChefPressAdmin.strings.useImage },
                multiple: false,
                library : { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $idInput.val(attachment.id);
                $preview.addClass('has-image').html('<img src="' + attachment.url + '" alt="" />');
                $removeBtn.removeClass('cp-hidden');
            });

            frame.open();
        },

        removeImage: function ($btn) {
            var $wrap = $btn.closest('.cp-media-field');
            $wrap.find('.cp-media-id').val('');
            $wrap.find('.cp-media-preview').removeClass('has-image').html('');
            $btn.addClass('cp-hidden');
        }
    };

    /* ==========================================================================
       Tag Input (recipe tags + allergen tags)
       ========================================================================== */
    var TagInput = {
        init: function () {
            // Main recipe tags.
            this.bindTagInput($('#cp-tag-input'), $('#cp-tags-list'), '_chefpress_tags[]');

            // Allergen tags.
            this.bindTagInput(
                $('#cp-allergen-tag-input'),
                $('#cp-allergen-tags-list'),
                '_chefpress_allergen_list[]'
            );
        },

        bindTagInput: function ($input, $list, fieldName) {
            if (!$input.length) return;

            $input.on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    TagInput.addTag($input, $list, fieldName);
                }
            });

            // Remove tag on × click.
            $(document).on('click', '.cp-tag__remove', function () {
                $(this).closest('.cp-tag').remove();
            });
        },

        addTag: function ($input, $list, fieldName) {
            var val = $input.val().trim().replace(/,+$/, '');
            if (!val) return;

            // Avoid duplicates.
            var exists = false;
            $list.find('input[type="hidden"]').each(function () {
                if ($(this).attr('name') !== fieldName) return;
                if ($(this).val().toLowerCase() === val.toLowerCase()) {
                    exists = true;
                }
            });

            if (exists) {
                $input.addClass('is-error');
                setTimeout(function () { $input.removeClass('is-error'); }, 800);
                return;
            }

            var $tag = $('<span class="cp-tag"></span>');
            $tag.append(document.createTextNode(val));
            $tag.append('<button type="button" class="cp-tag__remove" aria-label="Remove">×</button>');
            $tag.append($('<input type="hidden" />').attr('name', fieldName).val(val));

            $list.append($tag);
            $input.val('');
        }
    };

    /* ==========================================================================
       Star Rating Preview
       ========================================================================== */
    var StarRating = {
        init: function () {
            var $input = $('.cp-rating-input');
            if (!$input.length) return;

            var $preview = $input.siblings('.cp-star-preview');

            var render = function (rating) {
                rating = Math.max(0, Math.min(5, parseFloat(rating) || 0));
                var full  = Math.floor(rating);
                var half  = rating - full >= 0.5 ? 1 : 0;
                var empty = 5 - full - half;
                var html  = '';
                for (var i = 0; i < full;  i++) html += '<span class="full">★</span>';
                for (var j = 0; j < half;  j++) html += '<span class="half">★</span>';
                for (var k = 0; k < empty; k++) html += '<span class="empty">☆</span>';
                $preview.html(html);
            };

            render($input.val());
            $input.on('input', function () { render($(this).val()); });
        }
    };

    /* ==========================================================================
       WooCommerce Product Type Visibility
       ========================================================================== */
    var ProductTypeHandler = {
        init: function () {
            var $select = $('#product-type');
            if (!$select.length) return;

            this.toggle($select.val());

            $select.on('change', function () {
                ProductTypeHandler.toggle($(this).val());
            });
        },

        toggle: function (type) {
            var $tab = $('li.chefpress_recipe_tab, li[data-id="chefpress_recipe"]');
            if (type === 'recipe_product') {
                $tab.show();
                // Show ChefPress-specific fields.
                $('.show_if_recipe_product').show();
            } else {
                $tab.hide();
                $('.show_if_recipe_product').hide();
            }
        }
    };

    /* ==========================================================================
       Init
       ========================================================================== */
    $(function () {
        TabManager.init();
        CardCollapse.init();
        RepeaterToggle.init();
        RepeaterPreview.init();
        StepsRepeater.init();
        GroupsRepeater.init();
        GroupsRepeater.bindAddIngredient();
        GroupsRepeater.bindRemoveIngredient();
        MediaUploader.init();
        TagInput.init();
        StarRating.init();
        ProductTypeHandler.init();

        // Hide all repeater bodies by default (collapsed state).
        // Open the first one for UX.
        $('.cp-repeater-item').each(function (idx) {
            if (idx > 0) {
                $(this).find('> .cp-repeater-item__body').hide().addClass('is-collapsed');
            }
        });
    });

})(jQuery);
