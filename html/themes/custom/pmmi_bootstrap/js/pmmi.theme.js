/**
 * @file
 * PMMI theme js.
 */

(function ($, window, Drupal) {
  'use strict';

  /**
   * Attaches the iFrame resize behaviour for video field.
   */
  Drupal.behaviors.pmmiFrameResize = {
    attach: function (context, settings) {
      var $videoFrame = $('.field-name-field-video iframe');
      $videoFrame.once('video-resize').each(function () {
        var $thisFrame = $(this);
        var defaultDimensions = {
          width: $thisFrame.prop('width'),
          height: $thisFrame.prop('height')
        };
        var aspectRatio = defaultDimensions.width / defaultDimensions.height;
        $(window).once().on('resize load update', function (e, data) {
          if (e.type === 'update' && data.$videoFrame) {
            $thisFrame = data.$videoFrame;
          }
          if ($(window).width() < 1024) {
            var parentWidth = $thisFrame.parent().width();
            $thisFrame.width(parentWidth).height(parentWidth / aspectRatio);
          }
          else {
            $thisFrame.width(defaultDimensions.width).height(defaultDimensions.height);
          }
        });
      });
    }
  };

  /**
   * Attaches the video update behaviour for videos view on video node.
   */
  Drupal.behaviors.pmmiVideoUpdateAjax = {
    attach: function (context, settings) {
      // Add ajax links to videos view on Video node.
      if ($(context).is('.video-container')) {
        $('html, body').animate({
          scrollTop: $('.video-node .node-title').offset().top
        }, 100);
        $(window).trigger('update', {$videoFrame: $(context).find('iframe')});
      }
      $('#videos-view').once('video-view').each(function () {
        $(this).find('.default-mode-node').once('ajax').each(function () {
          var $item = $(this);
          var nodeID = $item.data('item-id');
          $item.find('.field-name-node-link a').prop('href', Drupal.url('pmmi-fields/replace-video/nojs/' + nodeID)).addClass('use-ajax');
        });
        Drupal.behaviors.AJAX.attach(context, settings);
      });
    }
  };

  /**
   * Common JS for elements theming.
   */
  Drupal.behaviors.pmmiCommonTheme = {
    attach: function (context, settings) {
      $('select').once('uniform').each(function () {
        $(this).uniform();
      });
      // Equal heights for blocks inside containers.
      $('.containers .row').once('matchHeight').each(function () {
        var $row = $(this);
        var $socialBlock = $row.find('.social-block');
        $row.imagesLoaded(function () {
          $('.col > .field > *', $row).matchHeight();
        });
        $.fn.matchHeight._afterUpdate = function (event, groups) {
          $socialBlock.each(function () {
            var $block = $(this);
            var $parent = $block.parent();
            var $title = $parent.find('.block-title');
            var newHeight = $parent.height() - $title.outerHeight(true);
            $block.height(newHeight);
            var messagesHeight = 0;
            $('.message', $block).each(function () {
              messagesHeight += $(this).outerHeight();
            });
            if (messagesHeight > newHeight) {
              $block.addClass('scroll');
            }
            else {
              $block.removeClass('scroll');
            }
          });
        };
      });
    }
  };


  /**
   * Cards block js stuff.
   *
   * @todo Implement responsive update based on drupal breakpoints.
   */
  Drupal.behaviors.pmmiCardsBlcok = {
    attach: function (context, settings) {
      var _this = this;
      $('.block-card').closest('.row').once('equal-height').each(function () {
        var $row = $(this);
        $(window).on('breakpointActivated', function (e, breakpoint) {
          if (breakpoint !== 'mobile') {
            _this.alignCards($row, breakpoint);
          }
          else {
            _this.alignCards($row, breakpoint);
          }
        });
      });
    },
    alignCards: function ($row, breakpoint) {
      _.delay(function () {
        $row.find('.flipper').removeAttr('style');
        $('.card-content', $row).each(function () {
          var $cardContent = $(this);
          var $cardParent = $cardContent.parent();
          var parentHeight = $cardParent.height();
          var cardHeight = $cardContent.height();
          var padding = $cardParent.outerHeight() - parentHeight;
          if (cardHeight > parentHeight) {
            if (breakpoint === 'mobile') {
              $cardContent.closest('.flipper').height(cardHeight + padding);
            }
            else {
              $row.find('.flipper').height(cardHeight + padding);
            }
          }
        });
      }, 50);
    }
  };

  /**
   * Main menu theming.
   */
  Drupal.behaviors.pmmiMainMenu = {
    attach: function () {
      $('.main-nav').once('main-nav').each(function () {
        var $dropdownToggle = $('a.dropdown-toggle');
        $dropdownToggle.each(function () {
          $(this).parent().find('.mega-nav').prepend($('<li>').addClass('only-mobile').append($(this).clone().toggleClass('dropdown-toggle')));
        });
        $(window).on('breakpointActivated', function (e, breakpoint) {
          if (breakpoint === 'mobile') {
            $dropdownToggle.on('click.mobile-toggler', function (e) {
              e.preventDefault();
              $(this).toggleClass('opened').parent().toggleClass('opened');
            });
          }
          else {
            $dropdownToggle.off('click.mobile-toggler');
          }
        });
      });
    }
  };

})(jQuery, window, Drupal);
