/** @since 1.0*/

jQuery(document).ready(function($){
    $( '#destroy-sessions' ).on( 'click', function( e ) {
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'coder_destroy_sessions_ajax',
                user_id: $( '#user_id' ).val()
            },
            beforeSend: function(){

            },
            success:function(response){
                $( '#coder-login-logout-status').html(response)
            }
        })
    })
})
        