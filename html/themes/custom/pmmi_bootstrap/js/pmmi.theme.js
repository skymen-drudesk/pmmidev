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
          $item.find('a').prop('href', Drupal.url('pmmi-fields/replace-video/' + nodeID)).addClass('use-ajax');
        });
        Drupal.behaviors.AJAX.attach(context, settings);
      });
    }
  };

})(jQuery, window, Drupal);
