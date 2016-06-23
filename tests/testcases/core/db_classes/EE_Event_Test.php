<?php

if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

/**
 *
 * EE_Event_Test
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
/**
 * @group core/db_classes
 */
class EE_Event_Test extends EE_UnitTestCase{
	public function test_primary_datetime(){
		$e = EE_Event::new_instance(array('EVT_name'=>'power1'));
		$e->save();
		$d = EE_Datetime::new_instance(array('EVT_ID'=>$e->ID()));
		$d->save();
		$primary_datetime = $e->primary_datetime();
		$this->assertEquals($d,$primary_datetime);
	}
	public function test_datetimes_ordered(){
		$e = EE_Event::new_instance(array('EVT_name'=>'power1'));
		$e->save();
		$d_exp = EE_Datetime::new_instance(array(
			'EVT_ID'=>$e->ID(),
			'DTT_EVT_start'=>time()-10,
			'DTT_EVT_end'=>time() - 5));
		$d_exp->save();
		$d_del = EE_Datetime::new_instance(array(
			'EVT_ID'=>$e->ID(),
			'DTT_EVT_start'=>time()-5,
			'DTT_EVT_end'=>time()+5,
			'DTT_deleted'=>true));
		$d_del->save();
		$d_ok= EE_Datetime::new_instance(array(
			'EVT_ID'=>$e->ID(),
			'DTT_EVT_start'=>time() - 1,
			'DTT_EVT_end'=>time() + 5));
		$d_ok->save();
		$ds = $e->datetimes_ordered();
		$this->assertArrayContains($d_exp,$ds);
		//$this->assertArrayDoesNotContain($d_del,$ds); @todo: bug, this assert actually fails because we have deactivated default where params
		$this->assertArrayContains($d_ok,$ds);
		//do it so it hides expired
		$ds = $e->datetimes_ordered(false);
		$this->assertArrayDoesNotContain($d_exp, $ds);
//		$this->assertArrayDoesNotContain($d_del, $ds); @todo: bug, this assert actually fails because we have deactivated
		$this->assertArrayContains($d_ok, $ds);
		//do it so it hides expired but shows deleted
		$ds = $e->datetimes_ordered(false, true);
		$this->assertArrayDoesNotContain($d_exp, $ds);
		$this->assertArrayContains($d_del, $ds);
		$this->assertArrayContains($d_ok, $ds);
		//do it so it shows the deleted one
		$ds = $e->datetimes_ordered(true, true);
		$this->assertArrayContains($d_exp, $ds);
		$this->assertArrayContains($d_del,$ds);
		$this->assertArrayContains($d_ok, $ds);
		//double-check the ordering.
		$first_d = array_shift($ds);
		$this->assertEquals($d_exp,$first_d);
		$second_d = array_shift($ds);
		$this->assertEquals($d_del,$second_d);
		$third_d = array_shift($ds);
		$this->assertEquals($d_ok,$third_d);
	}

	function test_active_status(){
		/** @type EE_Event $e */
		$e = EE_Event::new_instance( array( 'status' => 'publish' ) );
		$e->save();
		//echo "\n\n create Ticket";
		$t = EE_Ticket::new_instance( array(
			'TKT_start_date' => time() - 100,
			'TKT_end_date'   => time() + 50,
			'TKT_qty'        => 100,
			'TKT_sold'       => 0,
		) );
		$t->save();
		$d_now = EE_Datetime::new_instance( array(
			'EVT_ID'        => $e->ID(),
			'DTT_EVT_start' => time() - 100,
			'DTT_EVT_end'   => time() + 50
		) );
		$d_now->_add_relation_to( $t, 'Ticket' );
		$d_now->save();
		$d_exp = EE_Datetime::new_instance( array(
			'EVT_ID'        => $e->ID(),
			'DTT_EVT_start' => time() - 10,
			'DTT_EVT_end'   => time() - 5
		) );
		$d_exp->_add_relation_to( $t, 'Ticket' );
		$d_exp->save();
		$d_upcoming = EE_Datetime::new_instance( array(
			'EVT_ID'        => $e->ID(),
			'DTT_EVT_start' => time() + 10,
			'DTT_EVT_end'   => time() + 15
		) );
		$d_upcoming->_add_relation_to( $t, 'Ticket' );
		$d_upcoming->save();
		$this->assertEquals(EE_Datetime::active,$e->get_active_status( true ));
		$e->_remove_relation_to($d_now, 'Datetime');
		$this->assertEquals(EE_Datetime::upcoming,$e->get_active_status( true ));
		$e->_remove_relation_to($d_upcoming, 'Datetime');
		$this->assertEquals(EE_Datetime::expired,$e->get_active_status( true ));
	}



	function test_get_number_of_tickets_sold(){
		$e = EE_Event::new_instance();
		$e->save();
		$d_now = EE_Datetime::new_instance(array(
			'EVT_ID'=>$e->ID(),
			'DTT_EVT_start'=>time()-100,
			'DTT_EVT_end'=>time() - 50,
			'DTT_sold'=>5));
		$d_now->save();
		$d_exp = EE_Datetime::new_instance(array(
			'EVT_ID'=>$e->ID(),
			'DTT_EVT_start'=>time()-10,
			'DTT_EVT_end'=>time() - 5,
			'DTT_sold'=>15));
		$d_exp->save();
		$this->assertEquals(20,$e->get_number_of_tickets_sold());
		$e->_remove_relation_to($d_now, 'Datetime');
		$this->assertEquals(15,$e->get_number_of_tickets_sold());
	}


	/**
	 * @since 4.8.0
	 */
	function test_total_available_spaces() {
		//grab test scenarios.
		$scenarios = $this->scenarios->get_scenarios_by_type( 'event' );
		foreach ( $scenarios as $scenario ) {
			if ( $scenario->get_expected( 'total_available_spaces') ) {
				$this->assertEquals(
					$scenario->get_expected( 'total_available_spaces' ),
					$scenario->get_scenario_object()->total_available_spaces(),
					'Testing ' . $scenario->name
				);
			}
		}
	}


	/**
	 * @since 4.8.0
	 */
	function test_spaces_remaining_for_sale() {
		//grab test scenarios
		$scenarios = $this->scenarios->get_scenarios_by_type( 'event' );
		foreach ( $scenarios as $scenario ) {
			if ( $scenario->skip() ) {
				continue;
			}
			/** @type EE_Event $event */
			$event = $scenario->get_scenario_object();
			if ( $scenario->get_expected( 'total_remaining_spaces' ) !== false ) {
				$this->assertEquals(
					$scenario->get_expected( 'total_remaining_spaces' ),
					$event->spaces_remaining_for_sale(),
					'Testing ' . $scenario->name
				);
			}
		}
	}



	/**
	 * @since 4.8.0
	 */
	function test_spaces_remaining_for_sale_for_Event_Scenario_H() {
		//grab test scenario
		$scenario = $this->scenarios->get_scenario_by_name( 'Event Scenario H - Two Classes' );
		// verify
		if (
			! $scenario instanceof EE_Test_Scenario ||
			( $scenario instanceof EE_Test_Scenario && $scenario->name != 'Event Scenario H - Two Classes' )
		) {
				return;
		}
		/** @type EE_Event $event */
		$event = $scenario->get_scenario_object();
		if ( $scenario->get_expected( 'total_remaining_spaces' ) !== false ) {
			$this->assertEquals(
				$scenario->get_expected( 'total_remaining_spaces' ),
				$event->spaces_remaining_for_sale(),
				'Testing ' . $scenario->name
			);
		}
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after initial 6 ticket sales'
		);
		// now sell 2 more tickets
		$scenario->run_additional_logic( array( 'qty' => 2 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_4' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling an additional 2 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after an additional 2 ticket sales'
		);
		// now sell 2 more tickets
		$scenario->run_additional_logic( array( 'qty' => 2 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_2' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling an additional 2 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after an additional 2 ticket sales'
		);
		// now sell 2 more tickets
		$scenario->run_additional_logic( array( 'qty' => 2 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_0' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling an additional 2 tickets'
		);
		$this->assertEquals(
			EE_Datetime::sold_out,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after additional 6 ticket sales'
		);
	}






	/**
	 * @since 4.8.0
	 */
	function test_spaces_remaining_for_sale_for_Event_Scenario_I() {

		//grab test scenario
		$scenario = $this->scenarios->get_scenario_by_name( 'Event Scenario I - Four Tickets One Date' );
		// verify
		if (
			! $scenario instanceof EE_Test_Scenario ||
			( $scenario instanceof EE_Test_Scenario && $scenario->name != 'Event Scenario I - Four Tickets One Date' )
		) {
			return;
		}
		/** @type EE_Event $event */
		$event = $scenario->get_scenario_object();
		if ( $scenario->get_expected( 'total_remaining_spaces' ) !== false ) {
			$this->assertEquals(
				$scenario->get_expected( 'total_remaining_spaces' ),
				$event->spaces_remaining_for_sale(),
				'Testing ' . $scenario->name
			);
		}
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after initial setup'
		);
		// now sell first batch of tickets
		$scenario->run_additional_logic( array( 'tkt_id' => 1, 'qty' => 2 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 2, 'qty' => 2 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_20' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling first 4 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling first 4 tickets'
		);
		// now sell second batch of tickets - THIS IS WHEN IT USED TO SELL OUT
		$scenario->run_additional_logic( array( 'tkt_id' => 1, 'qty' => 2 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 3, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 4, 'qty' => 1 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_16' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling 8 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling 8 tickets'
		);
		// now sell third batch of tickets
		$scenario->run_additional_logic( array( 'tkt_id' => 1, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 2, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 3, 'qty' => 2 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_12' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling 12 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling 12 tickets'
		);
		// and a fourth batch of tickets
		$scenario->run_additional_logic( array( 'tkt_id' => 1, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 2, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 3, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 4, 'qty' => 1 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_8' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling 16 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling 16 tickets'
		);
		// and a fifth
		$scenario->run_additional_logic( array( 'tkt_id' => 2, 'qty' => 2 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 3, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 4, 'qty' => 1 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_4' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling 20 tickets'
		);
		$this->assertEquals(
			EE_Datetime::upcoming,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling 20 tickets'
		);
		// last batch
		$scenario->run_additional_logic( array( 'tkt_id' => 3, 'qty' => 1 ) );
		$scenario->run_additional_logic( array( 'tkt_id' => 4, 'qty' => 3 ) );
		$this->assertEquals(
			$scenario->get_expected( 'total_remaining_spaces_0' ),
			$event->spaces_remaining_for_sale(),
			'Testing ' . $scenario->name . ' after selling all 24 tickets'
		);
		$this->assertEquals(
			EE_Datetime::sold_out,
			$event->get_active_status( true ),
			$scenario->name . ' active_status after selling all 24 tickets'
		);
	}



	/**
	 * @return \EE_Event
	 * @throws \EE_Error
     */
	public function get_event_with_one_expired_active_and_upcoming_datetime() {
		/** @var EE_Event $event */
		$event = EE_Event::new_instance();
		$event->set_status('publish');
		$event->save();
		// last week event
		$last_week = EE_Datetime::new_instance(array(
			'EVT_ID'        => $event->ID(),
			'DTT_EVT_start' => time() - (10 * DAY_IN_SECONDS),
			'DTT_EVT_end'   => time() - (3 * DAY_IN_SECONDS),
		));
		$last_week->save();
		// this week event
		$this_week = EE_Datetime::new_instance(array(
			'EVT_ID'        => $event->ID(),
			'DTT_EVT_start' => time() - (3 * DAY_IN_SECONDS),
			'DTT_EVT_end'   => time() + (4 * DAY_IN_SECONDS),
		));
		$this_week->save();
		// next week event
		$next_week = EE_Datetime::new_instance(array(
			'EVT_ID'        => $event->ID(),
			'DTT_EVT_start' => time() + (4 * DAY_IN_SECONDS),
			'DTT_EVT_end'   => time() + (11 * DAY_IN_SECONDS),
		));
		$next_week->save();
		static $run_assertions = true;
		if ($run_assertions) {
			// confirm that $last_week datetime is expired
			$this->assertTrue($last_week->is_expired(), '$last_week datetime is NOT expired when it should be');
			$this->assertFalse($last_week->is_active(), '$last_week datetime IS active when it shouldn\'t be');
			$this->assertFalse($last_week->is_upcoming(), '$last_week datetime IS upcoming when it shouldn\'t be');
			// confirm that $this_week datetime is active
			$this->assertFalse($this_week->is_expired(), '$this_week datetime IS expired when it shouldn\'t be');
			$this->assertTrue($this_week->is_active(), '$this_week datetime is NOT active when it should be');
			$this->assertFalse($this_week->is_upcoming(), '$this_week datetime IS upcoming when it shouldn\'t be');
			// confirm that $next_week datetime is upcoming
			$this->assertFalse($next_week->is_expired(), '$next_week datetime IS expired when it shouldn\'t be');
			$this->assertFalse($next_week->is_active(), '$next_week datetime IS active when it shouldn\'t be');
			$this->assertTrue($next_week->is_upcoming(), '$next_week datetime is NOT upcoming when it should be');
			// now get all datetimes for event in ASC chronological order
			$datetimes = $event->datetimes();
			$this->assertCount(3, $datetimes, 'there should be three datetimes for this event');
			// don't run tests again
			$run_assertions = false;
		}
		return $event;
	}



	public function test_is_upcoming() {
		$event = $this->get_event_with_one_expired_active_and_upcoming_datetime();
		// confirm that $event is active
		$this->assertTrue($event->is_upcoming(), '$event is NOT upcoming when it should be');
	}



	public function test_is_active() {
		$event = $this->get_event_with_one_expired_active_and_upcoming_datetime();
		// confirm that $event is active
		$this->assertTrue($event->is_active(), '$event is NOT active when it should be');
	}



	public function test_is_active_or_upcoming() {
		$event = $this->get_event_with_one_expired_active_and_upcoming_datetime();
		// confirm that $event is active or upcoming
		$this->assertTrue($event->is_active_or_upcoming(), '$event is NOT active/upcoming when it should be');
	}



}
// End of file EE_Event_Test.php
// Location: tests/testcases/core/db_classes/EE_Event_Test.php
