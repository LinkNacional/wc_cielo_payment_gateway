/**
 * Lida com o dismiss da notificação do plugin de detecção de fraude
 */
jQuery(document).ready(function($) {
    // Interceptar o clique no botão dismiss da notificação específica do Cielo
    $(document).on('click', '.lkn-cielo-fraud-notice .notice-dismiss', function(e) {
        e.preventDefault();
        
        var $notice = $(this).closest('.notice');
        
        // Fazer requisição AJAX para salvar o dismiss permanentemente
        $.ajax({
            url: lknCieloDismissNotice.ajax_url,
            type: 'POST',
            data: {
                action: lknCieloDismissNotice.action,
                nonce: lknCieloDismissNotice.nonce
            },
            beforeSend: function() {
                // Opcional: adicionar loading indicator
                $notice.find('.notice-dismiss').addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    // Remover a notificação visualmente (WordPress já faz isso, mas garantindo)
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    console.error('Erro ao dispensar notificação:', response.data.message);
                    // Se falhou, manter a notificação visível
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX ao dispensar notificação:', error);
                // Se falhou, manter a notificação visível
            },
            complete: function() {
                $notice.find('.notice-dismiss').removeClass('loading');
            }
        });
        
        // Prevenir o comportamento padrão do WordPress
        return false;
    });
});