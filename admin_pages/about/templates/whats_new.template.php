<div class="changelog point-releases">
	<h3><?php echo _n( 'Major Release', 'Major Releases', 1 ); ?></h3>
	<p><?php printf( __( '<strong>Version %1$s</strong> is the initial release of the brand new EE4 codebase.', 'event_espresso'), EVENT_ESPRESSO_VERSION ); ?>
		<?php printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'http://eventespresso.com/wiki/change-log#4.1' ); ?>
 	</p>
</div>

<div class="changelog">
	<?php
	//maintenance mode on?
	if ( EE_Maintenance_Mode::instance()->level() == EE_Maintenance_Mode::level_2_complete_maintenance ) {
		?>
		<div class="ee-attention">
			<h2 class="ee-maintenance-mode-callout"><?php  _e('Event Espresso is in full maintenance mode.' , 'event_espresso'); ?></h2>
			<p>
			<?php 
				printf( 
					__('A previous version of Event Espresso has detected. But before anything else can happen, we need to know whether or not to migrate (copy over) your existing event data so that it can be utilized by EE4. For more instructions on what to do, please visit the %sEvent Espresso Maintenance%s page.', 'event_espresso'), 
					'<a href="admin.php?page=espresso_maintenance_settings">', 
					'</a>' 
				); 
			?>
			</p> 
		</div>
		<?php
	}
	?>	
	<h2 class="about-headline-callout"><?php _e('Introducing an improved event management system!', 'event_espresso'); ?></h2>
	<p><img class="about-overview-img" src="<?php echo EE_ABOUT_ASSETS_URL; ?>eventeditor-screen.jpg" /></p>
	<div class="feature-section col three-col about-updates">
		<div class="col-1">
			<img src="<?php echo EE_ABOUT_ASSETS_URL; ?>publish_meta_box.jpg">
			<h3>Optimized aesthetic</h3>
			<p>The new Event Espresso dashboard has a fresh, uncluttered design that embraces clarity and simplicity.</p>
		</div>
		<div class="col-2">
			<img src="<?php echo EE_ABOUT_ASSETS_URL; ?>registrations-overview.jpg">
			<h3>Improved management</h3>
			<p>We’ve made it easier to know who your customers are and how they’ve done business with you over time.</p>
		</div>
		<div class="col-3 last-feature">
			<img src="<?php echo EE_ABOUT_ASSETS_URL; ?>refined-bookkeeping.jpg">
			<h3>Refined bookkeeping</h3>
			<p>Registrations, payment, and transactions have been substantially improved. </p>
		</div>
	</div>

</div>