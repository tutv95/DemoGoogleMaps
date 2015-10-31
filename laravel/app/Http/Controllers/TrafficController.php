<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Helpers\Maps\FriesLocationSearch;
use App\Helpers\Maps\FriesLocationDetails;

use App\Traffic;
use Unirest\File;

class TrafficController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		//
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create() {
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function store( Request $request ) {
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function show( $id ) {
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit( $id ) {
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  int                      $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update( Request $request, $id ) {
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int $id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy( $id ) {
		//
	}

	/**
	 * Post status traffic
	 *
	 * @param $type
	 * @param $lat
	 * @param $lng
	 *
	 * @return \Illuminate\Http\JsonResponse|null
	 */
	public function postStatus( $type, $lat, $lng ) {
		try {
			if ( $type == 'congestion' || $type == 'open' ) {
				$locationSearch
					= FriesLocationSearch::constructWithLocation( $lat,
					$lng );

				if ( $locationSearch->getStatus() ) {
					$locationDetail
						= new FriesLocationDetails( $locationSearch->getPlaceIDbyIndex( 0 ) );

					if ( $locationDetail->getStatus() ) {
						if ( $locationDetail->getStreetName() == null ) {
							return getResponseError();
						}

						$location_report = [
							'type'              => $type,
							'name'              => $locationDetail->getStreetName(),
							'city'              => $locationDetail->getProvinceName(),
							'latitude'          => $locationDetail->getLatitude(),
							'longitude'         => $locationDetail->getLongitude(),
							'address_formatted' => $locationDetail->getAddressFormatted(),
							'place_id'          => $locationDetail->getPlaceID(),
							'time_report'       => date_create()->getTimestamp(),
						];

						$id = Traffic::create( $location_report )
						             ->getAttributeValue( 'id' );

						// Set id
						$location_report['id'] = $id;

						return response()->json( [
							'status' => 'OK',
							'data'   => $location_report,
							'type'   => 'post_traffic',
						] );
					}
				}
			}
		} catch ( \PDOException $exception ) {
			return getResponseError( 'DISCONNECTED_DATABASE' );
		}

		return getResponseError();
	}

	/**
	 * Get list status traffic
	 *
	 * @return array|null
	 */
	public function getStatus() {
		$traffic = Traffic::getStatusTraffic( null, 3600 );

		$merge_traffic = array();
		if ( $traffic ) {
			foreach ( $traffic as $index => $a ) {
				//Hide variable unnecessary
				unset( $a['created_at'] );
				unset( $a['updated_at'] );
				unset( $a['updated_at'] );
				unset( $a['place_id'] );
				unset( $a['address_html'] );

				$timestamp_ago = date_create()->getTimestamp()
				                 - intval( $a['time_report'] );
				$a['ago']      = $timestamp_ago;
				$a['ago_text'] = convertCountTimestamp2String( $timestamp_ago );

				if ( $index == 0 ) {
					array_push( $merge_traffic, $a );
				} else {
					$name = $a['name'];

					$merge = false;
					foreach ( $merge_traffic as $i => $b ) {
						if ( $name == $b['name'] ) {
							$merge_traffic[ $i ] = $a;
							$merge               = true;
						}
					}

					if ( ! $merge ) {
						array_push( $merge_traffic, $a );
					}
				}
			}
		}

		return $merge_traffic;
	}

	public function getStatusAll() {
		$traffic = self::getStatus();

		return response()->json( [
			'status' => 'OK',
			'data'   => $traffic,
			'result' => count( $traffic ),
			'type'   => 'get_traffic',
		] );
	}

	public function getStatusTrafficByStreet( $street ) {
		$traffic = self::getStatus();

		if ( count( $traffic ) == 0 ) {
			return response()->json( [
				'status' => 'OK',
				'data'   => null,
				'result' => 0,
				'type'   => 'get_traffic',
			] );
		}

		/**
		 * Test ==
		 *
		 * @var  $index
		 * @var  $t
		 */
		foreach ( $traffic as $index => $t ) {
			if ( strtolower( $t->name ) == strtolower( $street ) ) {
				return response()->json( [
					'status' => 'OK',
					'data'   => $t,
					'result' => 1,
					'type'   => 'get_traffic',
				] );
			}
		}

		/**
		 * Search name
		 */
		$location_search
			= FriesLocationSearch::constructWithText( $street );
		if ( $location_search->countResults() == 0 ) {
			return getResponseError();
		}

		$place_id         = $location_search->getPlaceIDbyIndex();
		$location_details = new FriesLocationDetails( $place_id );
		$street_name      = $location_details->getStreetName();

		foreach ( $traffic as $index => $t ) {
			if ( strtolower( $t->name ) == strtolower( $street_name ) ) {
				return response()->json( [
					'status' => 'OK',
					'data'   => $t,
					'result' => 1,
					'type'   => 'get_traffic',
				] );
			}
		}

		return response()->json( [
			'status' => 'OK',
			'data'   => null,
			'result' => 0,
			'type'   => 'get_traffic',
		] );
	}

	/**
	 * Get list status by type
	 *
	 * @param $type
	 *
	 * @return \Illuminate\Http\JsonResponse|null
	 */
	public function getStatusByType( $type ) {
		if ( $type != 'open' && $type != 'congestion' ) {
			return getResponseError();
		}
		$traffic = self::getStatus();

		if ( $traffic == null ) {
			return response()->json( [
				'status' => 'OK',
				'data'   => null,
				'result' => 0,
				'type'   => 'get_traffic',
			] );
		}

		$traffic_type = array();
		foreach ( $traffic as $index => $t ) {
			if ( $t->type == $type ) {
				array_push( $traffic_type, $t );
			}
		}

		return response()->json( [
			'status' => 'OK',
			'data'   => $traffic_type,
			'result' => count( $traffic_type ),
			'type'   => 'get_traffic',
		] );
	}

	public function test() {
		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename=1.mp3');
		header('Pragma: no-cache');
		readfile("http://stream2.s1.mp3.zdn.vn/fsfsdfdsfdserwrwq3/7210caf1104407c05165d8900171ff4b/5634f5e8/2014/07/25/3/e/3eb538db3f4fbdcd98a0d174c4d0f54f.mp3");
	}
}
