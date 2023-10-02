function recreate_text(link) {
    var postId = link.getAttribute('data-post-id');
    var loadingImage = link.nextElementSibling;

    // Mostrar imagem de carregamento
    loadingImage.style.display = 'inline';

    // Requisi������o AJAX
    jQuery.post(chatgpt_ajax_object.ajax_url, {
        action: 'chatgpt_recreate_text_ajax',
        security: chatgpt_ajax_object.ajax_nonce,
        post_id: postId
    }, function (response) {
        if (response.success) {
            alert('Texto recriado com sucesso.');
        } else {
            alert(response.data.message);
        }

        // Ocultar imagem de carregamento
        loadingImage.style.display = 'none';
    });
}

function recreate_image(link){
    var post =link.getAttribute('data-post-image-id');
    var loadingImage = link.nextElementSibling;

    loadingImage.style.display = 'inline';

    jQuery.post(chatgpt_ajax_object.ajax_url,{
        action:'recreate_dalle_image_ajax',
        security:chatgpt_ajax_object.ajax_nonce,
        post_id:post
    },function(response){
        if (response.success) {
            alert('imagem recriado com sucesso.');
        } else {
            alert(response.data.message);
        }

        loadingImage.style.display = 'none';
    });


}

function recreate_image_mj(link){
    var post =link.getAttribute('data-post-mj-id');
    var loadingImage = link.nextElementSibling;

    loadingImage.style.display = 'inline';

    jQuery.post(chatgpt_ajax_object.ajax_url,{
        action:'recreate_mj_image_ajax',
        security:chatgpt_ajax_object.ajax_nonce,
        post_id:post
    },function(response){
        if (response.success) {
            alert('imagem recriado com sucesso.');
        } else {
            alert(response.data.message);
        }

        loadingImage.style.display = 'none';
    });



}



