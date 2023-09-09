    <div id="chatgpt_model">
        <?php $count=0;?>
        <?php foreach ($models as $model_key => $model_name) : ?>
            <input type="radio" name="chatgpt_model" id="model_item-<?php echo $count?>" value="<?php echo esc_html($model_name)?>" class="gpt_input">
            <label for="model_item-<?php echo $count?>">
              <p><?php echo $model_name?></p>
            </label>
        <?php 
           $count++;
        endforeach; ?>
    </div>