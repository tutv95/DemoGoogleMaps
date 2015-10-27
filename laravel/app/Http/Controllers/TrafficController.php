<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Helpers;
use App\Helpers\Maps\FriesLocationSearch;
use App\Helpers\Maps\FriesLocationDetails;

use App\Traffic;
use DB;

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

	public function postStatus( $type, $lat, $lng ) {
		if ( $type == 'congestion' || $type == 'open' ) {
			$locationSearch = FriesLocationSearch::constructWithLocation( $lat,
				$lng );

			if ( $locationSearch->getStatus() ) {
				$locationDetail
					= new FriesLocationDetails( $locationSearch->getPlaceIDbyIndex( 0 ) );

				if ( $locationDetail->getStatus() ) {
					$location_report = [
						'type'              => $type,
						'name'              => $locationDetail->getName(),
						'latitude'          => $locationDetail->getLatitude(),
						'longitude'         => $locationDetail->getLongitude(),
						'address_formatted' => $locationDetail->getAddressFormatted(),
						'address_html'      => $locationDetail->getAddressHTML(),
						'place_id'          => $locationDetail->getPlaceID(),
						'time_report'       => date_create()->getTimestamp(),
					];

					// Insert & get ID
					$id = DB::table( 'traffic' )
					        ->insertGetId( $location_report );

					// Set id
					$location_report['id'] = $id;

					return response()->json( [
						'status' => 'SUCCESS',
						'data'   => $location_report,
					] );


				} else {
					Helpers\responseError();
				}
			} else {
				Helpers\responseError();
			}
		} else {
			Helpers\responseError();
		}
	}
}
