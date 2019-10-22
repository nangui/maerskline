<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        // dd($fileContent);

        foreach ($fileContent as $bls) {
            // highlight_string(json_encode($bls, JSON_PRETTY_PRINT));

            // Check if the destination is Senegal or Mali
            if (strtoupper($bls['pays_dest']) === 'SN' || strtoupper($bls['pays_dest']) === 'ML') {

                // Check if the bl has containers
                if(!empty($bls['conteneurs'])) {

                    $blCode = $this->isAplha($this->getContainerLastChar($bls['num_bl'])) ? $this->getContainerWithoutLastChar($bls['num_bl']) : $bls['num_bl'];

                    $bltype = $this->getBlType($bls['conteneurs'][0]);

                    $twenty_feets_number = $this->get20s($bls['conteneurs']);

                    $fourty_feets_number = $this->get40s($bls['conteneurs']);

                    $twenty_feets_code = $this->get20sContainers($bls['conteneurs']);

                    $fourty_feets_code = $this->get40sContainers($bls['conteneurs']);

                }

            }

            foreach($bls as $content) {
                // highlight_string(json_encode($content, JSON_PRETTY_PRINT));
                // if(is_array($content)) {
                //     var_export($content);
                // }

                // if(empty($content['contenesurs'])) {
                    // print_r($content);
                // }
            }
        }

        // dd($needed_data);

        // return view('gainde.index', ['content' => $needed_data]);
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
            if (substr($container[2], 0, 2) !== '40') {
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
