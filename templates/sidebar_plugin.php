<aside id="sidebar" class="widget-area">
    <h2>CONFIGURAÇÕES</h2>
    <div class="lumini_items">
        <input type="checkbox" name="CLEAN" id="clean" class="input_clean input_gpt">
        <label class="dark_light_radio" for="clean">
            <div class="radio-btn"></div>
            <span>CLEAN</span>
        </label>
        <input type="checkbox" name="DARK" id="dark" class="input_dark input_gpt">
        <label class="dark_light_radio" for="dark">
            <div class="radio-btn"></div>
            <span>DARK</span>
        </label>
    </div>

    <div class="authors">
    <p><strong class="autor_select">AUTOR:</strong></p>
         
    <ul class="authors_item">
         <?php foreach ($authors as $author) : ?>
            
                <li>
                <label for="chatgpt_author"><?php echo esc_html($author->display_name)?></label>
                <input type="radio" name="chatgpt_author" value="<?php echo esc_attr($author->ID)?>" class="author_input">
                </li>
         
        <?php endforeach; ?>
    </ul>
    </div>

    <div class="category">
    <p><strong>Selecionar categoria:</strong></p>
        <select name="chatgpt_category" id="chatgpt_category" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>>
            <?php foreach ($categories as $category) : ?>
                <option value="<?php echo esc_attr($category->term_id); ?>">
                    <?php echo esc_html($category->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="post_schedule">
    <p><strong>STATUS:</strong></p>
            <input type="radio" id="post_status_auto" name="post_status" value="auto" checked>
            <label for="post_status_auto">Gerar e Postar Automaticamente</label><br>
            
            
            <input type="radio" id="post_status_draft" name="post_status" value="draft" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>>
            <label for="post_status_draft">Colocar Post em Rascunho</label>
            <?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?><span>(Versão Premium)</span>
            <?php endif; ?>
            <br>
            <input type="radio" id="post_status_schedule" name="post_status" value="schedule" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>> 
            <label for="post_status_schedule">Agendar Postagem</label>
    </div>
</aside>