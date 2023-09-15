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
    <div class="sidebar_item">
    <div class="authors selection_item">
    <p class="autor_select" style="padding-left: 5%;"><strong>AUTOR:</strong></p>
         
    <div class="authors_item">
        <?php $count_author=0?>
         <?php foreach ($authors as $author) : ?>
                <input type="checkbox" name="chatgpt_author" value="<?php echo esc_attr($author->ID)?>" class="author_input" id="chatgpt_author-<?php echo $count_author?>">
                <label for="chatgpt_author-<?php echo $count_author?>" class="authors_input_label"><?php echo esc_html($author->display_name)?></label>
         <?php 
            $count_author++;
        endforeach; ?>
         </div>
    </div>

    <div class="category selection_item">
    <p class="category_select" style="padding-left: 5%;"><strong>CATEGORIA:</strong></p>
     <div class="category_item">
        <?php $count_category=0?>
         <?php foreach ($categories as $category) : ?>
                <input type="checkbox" name="chatgpt_category" value="<?php echo esc_attr($category->term_id)?>" class="category_input" id="chatgpt_category-<?php echo $count_category?>">
                <label for="chatgpt_category-<?php echo $count_category?>" class="authors_input_label"><?php echo esc_html($category->name)?></label>
         <?php 
            $count_category++;
        endforeach; ?>
        </div>
    </div>
</div>

    <div class="post_schedule">
    <p style="padding-left: 5%;"><strong>STATUS:</strong></p>
            <div class="lumini_items">
                <input class="schedule_input" type="radio" id="post_status_auto" name="post_status" value="auto" checked>
                <label class="input_schedul dark_light_radio" for="post_status_auto">
                    <div class="radio_schedule"></div>
                    PUBLICAR
                </label><br>
            </div>
            
            <div class="lumini_items">
                <input class="schedule_input"  type="radio" id="post_status_draft" name="post_status" value="draft" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>>
                <label class="input_schedule dark_light_radio" for="post_status_draft">
                    <div class="radio_schedule"></div>
                    RASCUNHO
                </label>
            </div>
            <?php if ( chatgpt_freemius_integration()->is_not_paying() ) : ?><span>(Versão Premium)</span>
            <?php endif; ?>
            <div class="lumini_items">
                <input class="schedule_input schedulee"  type="radio" id="post_status_schedule" name="post_status" value="schedule" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>> 
                <label class="input_schedule dark_light_radio" for="post_status_schedule">
                    <div class="radio_schedule"></div>
                    AGENDAR
                </label>
            </div>
            <span id="schedule_datetime_container">
                    <input type="datetime-local" name="schedule_datetime" id="schedule_datetime" value="" <?php echo chatgpt_freemius_integration()->is_not_paying() ? 'disabled' : ''; ?>>
            </span>
    </div>

    <div class="image_daali">
    <p style="padding-left: 5%;"><strong>IMAGEM:</strong></p>

    <div class="lumini_items">
                <input class="ia_image_input" type="radio" id="ia_dalle" name="ia_dalle" value="auto">
                <label class="input_ia_image dark_light_radio" for="ia_dalle">
                    <div class="radio-btn-image"></div>
                    DALL-E
                </label><br>
    </div>
    <div class="lumini_items">
                <input class="ia_image_input" type="radio" id="ia_midjournal" name="ia_midjournal" value="auto">
                <label class="input_ia_image dark_light_radio" for="ia_midjournal">
                    <div class="radio-btn-image"></div>
                    MIDJOURNAL
                </label><br>
    </div>
    <div class="lumini_items">
                <input class="ia_image_input" type="radio" id="ia_google_image" name="ia_google_image" value="auto">
                <label class="input_ia_google dark_light_radio" for="ia_google_image">
                    <div class="radio-btn-image"></div>
                    GOOGLE
                </label><br>
    </div>
    <div class="lumini_items">
                <input class="ia_image_input upload-image" type="radio" id="ia_send" name="ia_send" value="auto">
                <label class="input_ia_image dark_light_radio" for="ia_send">
                    <div class="radio-btn-image"></div>
                    ENVIAR IMAGEM
                </label><br>
    </div>
    <input type="file" name="image_upload" id="image_upload">
    </div>
</aside>