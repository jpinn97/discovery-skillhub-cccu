jQuery(document).ready(function ($) {
    if( parseInt(wpforo_widgets.is_live_notifications_on) && typeof wpforo_check_notifications === 'function'){
        setTimeout(wpforo_check_notifications, parseInt(wpforo_widgets.live_notifications_start), parseInt(wpforo_widgets.live_notifications_interval));
    }

    $(document).on("keydown", function (e) {
        if( e.code === 'Escape' ) $('.wpf-notifications').slideUp(250, 'linear');
    });

    $(document).on('click', '.wpf-alerts:not(.wpf-processing)', function () {
        var notifications = $('.wpforo-subtop').find('.wpf-notifications');
        $('.wpf-notifications').not(notifications).slideUp(250, 'linear');
        if( notifications.is(':visible') ){
            notifications.slideUp(250, 'linear');
        }else{
            wpforo_load_notifications( $(this) );
            notifications.slideDown(250, 'linear');
        }
    });

    $(document).on('click', '.wpf-widget-alerts:not(.wpf-processing)', function () {
        var notifications = $('.wpf-widget-alerts').parents('.wpf-prof-wrap').find('.wpf-notifications');
        $('.wpf-notifications').not(notifications).slideUp(250, 'linear');
        if( notifications.is(':visible') ){
            notifications.slideUp(250, 'linear');
        }else{
            wpforo_load_notifications( $(this) );
            notifications.slideDown(250, 'linear');
        }
    });

    $(document).on('click', '.wpf-action.wpf-notification-action-clear-all', function(){
        var foro_n = $(this).data('foro_n');
        if( foro_n ){
            $('.wpf-notifications').slideUp(250, 'linear');
            $.ajax({
                type: 'POST',
                url: wpforo_widgets.ajax_url,
                data:{
                    foro_n: foro_n,
                    action: 'wpforo_clear_all_notifications'
                }
            }).done(function(r){
                if(r){
                    $('.wpf-notifications .wpf-notification-actions').hide();
                    $('.wpf-notifications .wpf-notification-content').html(r);
                    wpforo_bell(0);
                }
            });
        }
    });

    function do_wpforo_ajax_widget( elem, is_on_load ){
        var j = elem.data( 'json' );
        if( j ){
            if( typeof j !== 'object' ) j = JSON.parse( j );
            if( j['instance']   !== undefined && typeof j['instance']   !== 'object' ) j['instance']   = JSON.parse( j['instance'] );
            if( j['topic_args'] !== undefined && typeof j['topic_args'] !== 'object' ) j['topic_args'] = JSON.parse( j['topic_args'] );
            if( j['post_args']  !== undefined && typeof j['post_args']  !== 'object' ) j['post_args']  = JSON.parse( j['post_args'] );

            if( j['boardid'] !== undefined ){
                var ajax_url = wpforo_widgets.ajax_url.replace( /[&?]wpforo_boardid=\d*/i, '' );
                ajax_url += (/\?/.test( ajax_url ) ? '&' : '?') + 'wpforo_boardid=' + j['boardid'];

                if( j['instance'] !== undefined && j['instance']['refresh_interval'] !== undefined ){
                    var interval = parseInt( j['instance']['refresh_interval'] );
                }

                if( j['instance']   !== undefined ) j['instance']   = JSON.stringify( j['instance'] );
                if( j['topic_args'] !== undefined ) j['topic_args'] = JSON.stringify( j['topic_args'] );
                if( j['post_args']  !== undefined ) j['post_args']  = JSON.stringify( j['post_args'] );

                var do_ajax = true;
                if( is_on_load && elem.hasClass( 'wpforo-ajax-widget-onload-false' ) ) do_ajax = false;

                if( do_ajax ){
                    $.ajax({
                        type: 'POST',
                        url: ajax_url,
                        data: j,
                    }).done(function( r ){
                        if( r.success ) elem.html( r.data['html'] );

                        if( !isNaN( interval ) && interval > 0 ){
                            setTimeout( function(){
                                do_wpforo_ajax_widget( elem );
                            }, interval * 1000 );
                        }
                    });
                }else{
                    if( !isNaN( interval ) && interval > 0 ){
                        setTimeout( function(){
                            do_wpforo_ajax_widget( elem );
                        }, interval * 1000 );
                    }
                }
            }
        }
    }

    function do_wpforo_ajax_widgets(){
        var wdgts = $( '.wpforo-widget-wrap .wpforo-ajax-widget[data-json]' );
        if( wdgts.length ){
            wdgts.each(function( k, v ){
                do_wpforo_ajax_widget( $(v), true );
            });
        }
    }

    do_wpforo_ajax_widgets();
});

function wpforo_bell( wpf_alerts ){
    wpf_alerts = parseInt(wpf_alerts);
    if( wpf_alerts > 0 ){
        var wpforo_bell = '';
        var wpf_tooltip = '';
        if ( typeof window.wpforo_phrase === "function" ) {
            var wpforo_notification_phrase =  wpforo_phrase('You have a new notification');
            if( wpf_alerts > 1 ) wpforo_notification_phrase = wpforo_phrase('You have new notifications');
            wpf_tooltip = 'wpf-tooltip="' + wpforo_notification_phrase + '" wpf-tooltip-size="middle"';
        }
        wpforo_bell = '<div class="wpf-bell" ' + wpf_tooltip + '><i class="fas fa-bell"></i> <span class="wpf-alerts-count">' + wpf_alerts + '</span></div>';
        jQuery('.wpf-alerts').addClass('wpf-new');
        jQuery('.wpf-widget-alerts').addClass('wpf-new');
    } else {
        wpforo_bell = '<div class="wpf-bell"><i class="far fa-bell"></i></div>';
        jQuery('.wpf-alerts').removeClass('wpf-new');
        jQuery('.wpf-widget-alerts').removeClass('wpf-new');
    }
    jQuery('.wpf-alerts').html(wpforo_bell);
    jQuery('.wpf-widget-alerts').html(wpforo_bell);
}

var wpforo_check_notifications_timeout;
function wpforo_check_notifications( wpforo_check_interval ) {
    wpforo_check_interval = parseInt(wpforo_check_interval);
    if( isNaN(wpforo_check_interval) ) wpforo_check_interval = 60000;
    var getdata = jQuery('.wpf-notifications').is(':visible');
    jQuery.ajax({
        type: 'POST',
        url: wpforo_widgets.ajax_url,
        data:{
            getdata: getdata,
            action: 'wpforo_notifications'
        },
        success: wpforo_notifications_ui_update,
        complete: function() {
            wpforo_check_notifications_timeout = setTimeout(wpforo_check_notifications, wpforo_check_interval, wpforo_check_interval);
        },
        error: function () {
            clearTimeout(wpforo_check_notifications_timeout);
        }
    });
}

function wpforo_load_notifications($this){
    $this.addClass('wpf-processing');
    jQuery('.wpf-notifications .wpf-notification-content').html('<div class="wpf-nspin"><i class="fas fa-spinner fa-spin"></i></div>');
    jQuery.ajax({
        type: 'POST',
        url: wpforo_widgets.ajax_url,
        data:{
            getdata: 1,
            action: 'wpforo_notifications'
        },
        success: wpforo_notifications_ui_update,
        error: function () {
            clearTimeout(wpforo_check_notifications_timeout);
        }
    }).always(function(){
        $this.removeClass('wpf-processing');
    });
}

function wpforo_notifications_ui_update(response){
    var wpf_alerts = parseInt(response.data.alerts);
    if( wpf_alerts > 0 ){
        jQuery('.wpf-notifications .wpf-notification-actions').show();
    } else {
        jQuery('.wpf-notifications .wpf-notification-actions').hide();
    }
    if( response.data.notifications ) jQuery('.wpf-notifications .wpf-notification-content').html( response.data.notifications );
    wpforo_bell( wpf_alerts );
}
