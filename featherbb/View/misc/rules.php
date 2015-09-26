<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

$feather->hooks->fire('view.misc.rules.start');
?>
<div id="rules" class="block">
    <div class="hd"><h2><span><?php _e('Forum rules') ?></span></h2></div>
    <div class="box">
        <div id="rules-block" class="inbox">
            <div class="usercontent"><?= $feather->forum_settings['o_rules_message'] ?></div>
        </div>
    </div>
</div>

<?php
$feather->hooks->fire('view.misc.rules.end');
