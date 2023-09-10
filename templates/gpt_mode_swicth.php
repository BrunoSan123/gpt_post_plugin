    <div id="chatgpt_model">
        <?php $count=0; $icons_modifier=array('ray','stars')?>
        <?php foreach ($models as $model_key => $model_name) : ?>
            <input type="radio" name="chatgpt_model" id="model_item-<?php echo $count?>" value="<?php echo esc_html($model_name)?>" class="gpt_input" <?php if ($count === 0) echo 'checked="checked"'; ?>>
            <label for="model_item-<?php echo $count?>" class="gpt_label_model" data-target="<?php echo $icons_modifier[$count]?>">
              <?php echo $icons[$count]?>
              <p><?php echo $model_name?></p>
            </label>
        <?php 
           $count++;
        endforeach; ?>
    </div>