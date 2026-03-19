/**
 * Dev ChefPress for WooCommerce — Frontend JS
 * Smooth interactions for the recipe frontend.
 */
(function ($) {
    'use strict';

    $(function () {

        /* Lazy-load images with IntersectionObserver */
        if ('IntersectionObserver' in window) {
            var $lazyImgs = $('.cp-recipe img[data-src]');
            if ($lazyImgs.length) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var $img = $(entry.target);
                            $img.attr('src', $img.data('src')).removeAttr('data-src');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { rootMargin: '200px' });

                $lazyImgs.each(function () {
                    observer.observe(this);
                });
            }
        }

        /* Smooth scroll when clicking ingredient check */
        $(document).on('click', '.cp-ingredient-list__item', function () {
            $(this).toggleClass('is-checked');
        });

    });

})(jQuery);
