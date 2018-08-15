(function ($) {

  Drupal.behaviors.pmmiVideoGallerySlider = {
    attach: function (context, settings) {
      // Connect slick slider to each
      // class="video-gallery--slider" DOM element.
      $('.video-gallery--slider').each(function () {
        // Navigation container.
        let $nav = $(this).find('.video-gallery__slider-navigation'),
          $slickSlider = $(this).find('.video-gallery__slider > div');
        // Counter container.
        let $countBlock = $nav.find('.video-gallery__slider-counter');

        // Add text in counter container in format: "1 of 6".
        $slickSlider.on('init reInit afterChange', function (event, slick, currentSlide) {
          let i = (currentSlide ? currentSlide : 0) + 1;
          $countBlock.text(i + ' ' + Drupal.t('of') + ' ' + slick.slideCount);
        });

        // Initiate slick slider.
        $slickSlider.once().slick({
          dots: true,
          slide: '.video-gallery__slider-item',
          infinite: false,
          slidesToShow: 3,
          slidesToScroll: 1,
          lazyLoad: 'ondemand',
          nextArrow: '<span class="video-gallery__slider-arrow video-gallery__slider-arrow--right"></span>',
          prevArrow: '<span class="video-gallery__slider-arrow video-gallery__slider-arrow--left"></span>',
          appendArrows: $nav,
          appendDots: $nav,
          centerPadding: '100px',
          responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1,
              }
            },
            {
              breakpoint: 768,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                dots: false
              }
            }
          ]
        });
      });
    }
  };

  Drupal.behaviors.pmmiVideoGalleryExpanded = {
    attach: function (context, settings) {
      $('.video-gallery--expanded').each(function () {
        let $_that = $(this);

        // Replace
        $(this).find('.video-gallery__expanded-link').on('click', function (e) {
          e.preventDefault();

          let videoUrl = $(this).attr('data-video-url'),
            imageUrl = $(this).attr('data-image-url');

          $_that.find('.video-gallery__expanded-video').attr('href', videoUrl);
          $_that.find('.video-gallery__expanded-image').attr('src', imageUrl);
        });

        $(this).find('.video-gallery__expanded-items-title').on('click', function (e) {
          // Close container if current container is active.
          if ($(this).hasClass('active')) {
            $(this).toggleClass('active');

            $('.video-gallery__expanded-items-title').removeClass('active');
            $('.video-gallery__expanded-items-wrapper').removeClass('active');
          }
          else {
            // Collapse other containers and make active clicked container.
            $('.video-gallery__expanded-items-title').removeClass('active');
            $('.video-gallery__expanded-items-wrapper').removeClass('active');

            $(this).toggleClass('active');
            $_that.find('.video-gallery__expanded-items-wrapper').toggleClass('active');
          }
        });
      });
    }
  };
})(jQuery, window, Drupal);
