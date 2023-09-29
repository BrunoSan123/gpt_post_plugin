//Javascript que oculta e mostra o calendário para agendamento de postagem


if(document.body.classList.contains("toplevel_page_chatgpt_plugin")){
    document.addEventListener('DOMContentLoaded', function() {
        var postStatusRadios = document.getElementsByName('post_status');
        var scheduleDatetimeContainer = document.getElementById('schedule_datetime_container');
    
        function updateScheduleDatetimeContainerVisibility() {
            if (document.querySelector('input[name="post_status"]:checked').value === 'schedule') {
                scheduleDatetimeContainer.style.display = '';
            } else {
                scheduleDatetimeContainer.style.display = 'none';
            }
        }
    
        postStatusRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateScheduleDatetimeContainerVisibility();
            });
        });
    
        updateScheduleDatetimeContainerVisibility();
    });
    
    
    //Javascript que salva o PROMPT
    document.getElementById('save_prompt').addEventListener('click', function() {
        var promptValue = document.getElementById('chatgpt_prompt').value;
    
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                alert('Prompt salvo com sucesso.');
            }
        };
    
        xhttp.open('POST', ajaxurl, true);
        xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhttp.send('action=save_chatgpt_prompt&prompt=' + encodeURIComponent(promptValue));
    });
    
    
    
    
    //verifica se as palavras chaves foram digitadas
    
    function checkKeywords(event) {
        var keywords = document.getElementById("chatgpt_keywords").value;
        if (keywords.trim() == "") {
            alert("Por favor, preencha pelo menos uma palavra-chave antes de gerar textos.");
            event.preventDefault();  // Impede a submissão do formulário
        }
    }

}

