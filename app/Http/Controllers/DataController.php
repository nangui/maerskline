<?php

namespace App\Http\Controllers;

use App\Bl;
use App\Loading;
use App\PortCodes;
use App\Shipping;
use App\Vesselle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('gainde.index');
    }

    /**
     * Save file to database.
     *
     * @param Request $request
     * @return Response
     */
    public function import(Request $request)
    {
        $request->validate(['gainde_file' => 'required|max:5100']);

        $file = Storage::putFile('manifiste', $request->file('gainde_file'));

        // Get file from Storage
        $fileToRead = Storage::get($file);
        $fileContent = json_decode($fileToRead, true);

        foreach ($fileContent as $bls) {
            // highlight_string(json_encode($bls, JSON_PRETTY_PRINT));

            // Check if the destination is Senegal or Mali
            if (strtoupper($bls['pays_dest']) === 'SN' || strtoupper($bls['pays_dest']) === 'ML') {

                // Check if the bl has containers
                if(count($bls['conteneurs']) != 0) {

                    $blCode = $this->isAplha($this->getContainerLastChar($bls['num_bl'])) ? $this->getContainerWithoutLastChar($bls['num_bl']) : $bls['num_bl'];

                    $bltype = $this->getBlType($bls['conteneurs'][0]);

                    $twenty_feets_number = $this->get20s($bls['conteneurs']);

                    $fourty_feets_number = $this->get40s($bls['conteneurs']);

                    $twenty_feets_code = $this->get20sContainers($bls['conteneurs']);

                    $fourty_feets_code = $this->get40sContainers($bls['conteneurs']);

                    $found = empty(Bl::where('bl_number', $blCode)->select('bl_number')->get()[0]);

                    // dd(Bl::where('bl_number', $blCode)->select('bl_number')->get()[0]);

                    if(empty(PortCodes::where('port_code', trim($bls['lieu_embarq']))->select('id')->get()[0])) {
                        PortCodes::create([
                            'port_code' => trim($bls['bateau']['manifMoyenTransport']),
                            'port_city' => 'DEFAULT'
                        ]);

                        // dd(trim($bls['lieu_embarq']));
                    }

                    if(empty(Vesselle::where('name', trim($bls['bateau']['manifMoyenTransport']))->get()[0])) {
                        Vesselle::create([
                            'name' => trim($bls['bateau']['manifMoyenTransport']),
                            'shipping_id' => 1
                        ]);
                        // dd(trim($bls['bateau']['manifMoyenTransport']));
                    }

                    if($found) {
                        Bl::create([
                            'bl_number' => $blCode,
                            'arrival_date' => Carbon::parse($bls['bateau']['manifDateArrivee']),
                            'cargo_type' => $bltype,
                            'shipper' => $bls['nom_exp'],
                            'order' => $bls['destinaire'],
                            'commodity' => $bls['commodity'],
                            'number_of_20' => $twenty_feets_number,
                            'number_of_40' => $fourty_feets_number,
                            'container_20' => $twenty_feets_code,
                            'container_40' => $fourty_feets_code,
                            'port_id' => PortCodes::where('port_code', trim($bls['lieu_embarq']))->select('id')->get()[0]->id,
                            'vesselle_id' => Vesselle::where('name', trim($bls['bateau']['manifMoyenTransport']))->select('id')->get()[0]->id,
                        ]);
                    } else {
                        Bl::where('bl_number', $blCode)->update([
                            'number_of_20' => Bl::where('bl_number', $blCode)->select('number_of_20')->get()[0]->number_of_20 + $twenty_feets_number,
                            'number_of_40' => Bl::where('bl_number', $blCode)->select('number_of_40')->get()[0]->number_of_40 + $fourty_feets_number,
                            'container_20' => Bl::where('bl_number', $blCode)->select('container_20')->get()[0]->container_20 . $twenty_feets_code,
                            'container_40' => Bl::where('bl_number', $blCode)->select('container_40')->get()[0]->container_40 . $fourty_feets_code,
                        ]);
                    }

                }

            }
        }

        return redirect()->back();
    }

    /**
     * Get bl type through first container.
     *
     * @param Array $firstContainer
     * @return Bltype
     */
    public function getBlType($firstContainer)
    {
        return strtolower(substr($firstContainer[3], 0, 2)) === 're' ? 'REEFER' : 'DRY'  ;
    }

    /**
     * Get number of 20 feet containers.
     *
     * @param Array $blContainers
     * @return Number
     */
    public function get20s($blContainers)
    {
        $n_20 = 0;

        foreach ($blContainers as $container) {
            if (substr($container[2], 0, 2) === '20') {
                $n_20++;
            }
        }

        return $n_20;
    }

    /**
     * Get number of 40 feet containers.
     *
     * @param Array $blContainers
     * @return Number
     */
    public function get40s($blContainers)
    {
        $n_40 = 0;

        foreach ($blContainers as $container) {
            if (substr($container[2], 0, 2) === '40' || substr($container[2], 0, 2) === '45') {
                $n_40++;
            }
        }

        return $n_40;
    }

    /**
     * Get the concatenated of 20 feet containers' id.
     *
     * @param Array $blContainers
     * @return String
     */
    public function get20sContainers($blContainers)
    {
        $n_20 = 0;

        foreach ($blContainers as $container) {
            if (substr($container[2], 0, 2) === '20') {
                $n_20 .= trim($container[0]) . ' | ';
            }
        }

        return $n_20;
    }

    /**
     * Get the concatenated of 40 feet containers' id.
     *
     * @param Array $blContainers
     * @return String
     */
    public function get40sContainers($blContainers)
    {
        $c_40 = "";

        foreach ($blContainers as $container) {
            if (substr($container[2], 0, 2) !== '40') {
                $c_40 .= trim($container[0]) . ' | ';
            }
        }

        return $c_40;
    }

    /** */
    public function getContainerLastChar($container)
    {
        return substr($container, -1);
    }

    /** */
    public function isAplha($string)
    {
        return ctype_alpha($string);
    }

    /** */
    public function getContainerWithoutLastChar($container)
    {
        return substr($container, 0, strlen($container) -1);
    }

}
