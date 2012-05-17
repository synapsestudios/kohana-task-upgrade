<?php if (count($upgrades) === 0): ?>
Application Not Installed
<?php else: ?>
-- Upgrades installed --
<?php foreach($upgrades as $version => $timestamp): ?>
  - Version <?php echo $version ?> installed on <?php echo date_create('@'.$timestamp)->format(DATE_W3C).PHP_EOL ?>
<?php endforeach; ?>
<?php endif; ?>