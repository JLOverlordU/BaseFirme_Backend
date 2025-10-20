<?php

namespace App\Http\Traits\Parametrizaciones\Archivos;

use Exception;
use Aws\S3\S3Client;
use App\Utils\Env\EnvHelper;
use Illuminate\Http\Request;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class FileBaseClass
{

    private $time_token;
    private $buckets;
    private $paths;
    private $s3;
    private $aplicativo;

    public function __construct($aplicativo = 'siva')
    {
        $this->time_token = '+90 minutes';

        $this->buckets = [
            'cliente' => [
                'desarrollo' => '',
                'alpha'      => '',
                'calidad'    => '',
                'produccion' => '',
            ],
        ];

        $this->paths = [
            'cliente' => [
                'local'      => '',
                'testing'    => '',
                'desarrollo' => '',
                'alpha'      => '',
                'uat'        => '',
                'calidad'    => '',
                'produccion' => '',
            ],
        ];

        $this->aplicativo = $aplicativo;
    }

    private function create_s3_client()
    {
        $this->s3 = new S3Client([
            'region'  => EnvHelper::get('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key'    => ($this->aplicativo == 'siva') ? EnvHelper::get('AWS_ACCESS_KEY_ID') : EnvHelper::get('AWS_ACCESS_KEY_ID_SUPLOS'),
                'secret' => ($this->aplicativo == 'siva') ? EnvHelper::get('AWS_SECRET_ACCESS_KEY') : EnvHelper::get('AWS_SECRET_ACCESS_KEY_SUPLOS'),
            ]
        ]);
    }

    public function getUrlImg($typeAmbient, $urlFile, $ambiente, $tamaño)
    {
        $urlFileAux = preg_replace('/_\.(?=[a-zA-Z]*$)/', "_$tamaño.", $urlFile);
        $resp['flag'] = false;
        if ($ambiente == 's3') {
            $resp = $this->getFileS3($typeAmbient, $urlFileAux);
            if (!$resp['flag']) {
                $resp = $this->getFileS3($typeAmbient, $urlFile);
            }
        } else {
            $resp = $this->getFileLocal($typeAmbient, $urlFileAux);
            if (!$resp['flag']) {
                $resp = $this->getFileLocal($typeAmbient, $urlFile);
            }
        }
        return $resp;
    }

    public function getFileLocal($typeAmbient, $urlFile){
        try {
            if (trim($urlFile) != '') {
                $keyName = $this->paths[$typeAmbient][EnvHelper::get('APP_ENV')] . $urlFile;
                $url = url('\\') . $keyName;
                $url = str_replace('\\', '/', $url);
                $url = preg_replace('/([^:])(\/{2,})/', '$1/', $url);
                if (file_exists(public_path() . '/' . $keyName) && !is_null($urlFile) && $urlFile != '') {
                    return $this->createResp(true, 'Archivo encontrado', $url);
                }
                return $this->createResp(false, 'Archivo no encontrado', '');
            }
            return $this->createResp(false, 'Url vacía.', '');
        } catch (\Throwable $th) {
            throw new Exception('(fn => getFileLocal, Error => ' . $th->getMessage() . ')');
        }
    }

    public function getFileS3($typeAmbient, $urlFile, $segundo_intento = false){
        try {
            if (env('APP_ENV', 'local') != 'local' && trim($urlFile) != '') {
                $urlFile = $this->clearUrl($urlFile);
                $this->create_s3_client();
                $keyName = $this->paths[$typeAmbient][EnvHelper::get('APP_ENV')] . $urlFile;
                $bucket = $this->buckets[$typeAmbient][EnvHelper::get('APP_ENV')];
                if ($this->doesFileExistsS3($bucket, $keyName)) {
                    $cmd = $this->s3->getCommand('GetObject', [
                        'Bucket' => $bucket,
                        'Key' => $keyName
                    ]);
                    $request = $this->s3->createPresignedRequest($cmd, $this->time_token);
                    
                    return $this->createResp(true, 'Archivo encontrado', (string)$request->getUri());
                } else if (!$segundo_intento) {
                    return (new self('suplos'))->getFileS3('proveedoresSuplos', $urlFile, true);
                }
                return $this->createResp(false, 'Archivo no encontrado', '');
            }
            return $this->createResp(false, 'Instancias locales sin acceso a s3 o url vacía.', '');
        } catch (AwsException $e) {
            throw new Exception('(fn => getFileS3, Error => ' . $e->getMessage() . ')');
        } catch (Exception $e) {
            throw new Exception('(fn => getFileS3, Error => ' . $e->getMessage() . ')');
        }
    }

    public function putFileLocal($file, $typeAmbient, $urlBase){

        try {

            //validación archivo 
            $this->validaArchivo($file);
            $result = Storage::disk('public')->put($urlBase, $file);

            return $this->clearUrl($result);
            // return $file->getClientOriginalName();
            
        } catch (\Throwable $th) {
            throw new Exception('(fn => putFileLocal, Error => ' . $th->getMessage() . ')');
        }
    }

    public function putImgLocal($file, $typeAmbient, $urlBase, $dimensiones){
        try {
            //validación archivo tipo imagen
            $this->validaImg($file);

            $keyName = public_path($this->paths[$typeAmbient][EnvHelper::get('APP_ENV')] . $urlBase);

            //Se crea la carpeta si no existe
            if (!file_exists($keyName)) {
                mkdir($keyName, 0777, true);
            }

            $fileBaseName = $file->getClientOriginalName();
            $filename = str_replace(" ", "", pathinfo($fileBaseName, PATHINFO_FILENAME)) . '_' . uniqid() . '_';

            $resultRed = $this->resizeImage($file, $dimensiones);

            $resultRed['img_base']->save($keyName . $filename . '.' . $file->extension());

            if (is_array($resultRed['resize'])) {
                for ($i = 0; $i < count($resultRed['resize']); $i++) {
                    $resultRed['resize'][$i]["img"]->save($keyName . $filename . $resultRed['resize'][$i]['width'] . '.' . $file->extension());
                }
            }

            $url1 = $this->clearUrl($urlBase . '/' . str_replace($keyName, "", $filename . '.' . $file->extension()));

            return $this->createResp(true, 'Guardado', $url1);
        } catch (\Throwable $th) {
            throw new Exception('(fn => putImgLocal, Error => ' . $th->getMessage() . ')');
        }
    }

    public function putImgS3($file, $typeAmbient, $urlBase, $dimensiones){
        try {
            $keyName = "";
            $id = uniqid();
            if (env('APP_ENV', 'local') != 'local') {
                $tempPath = public_path() . '/temp/s3/';
                //creacion carpeta archivos temporales s3
                if (!file_exists($tempPath)) {
                    mkdir($tempPath, 0777, true);
                }
                //validación archivo tipo imagen
                $this->validaImg($file);
                $resultRed = $this->resizeImage($file, $dimensiones);
                $filename = $id . '_.' . File::extension($file->getClientOriginalName());
                $keyName = $this->clearUrl($urlBase . '/' . $filename);

                //se guarda la foto base                
                $respBase = $this->putS3Obj($typeAmbient, $keyName, $resultRed['img_base']->basePath());

                if (is_array($resultRed['resize'])) {
                    for ($i = 0; $i < count($resultRed['resize']); $i++) {
                        $photo = $resultRed['resize'][$i]["img"]->save($tempPath . uniqid() . '.' . $file->extension());
                        $filenameAux = $id . '_' . $resultRed['resize'][$i]['width'] . '.' . File::extension($file->getClientOriginalName());
                        $keyNameAux = $this->clearUrl($urlBase . '/' . $filenameAux);
                        //se guarda foto re-dimensionada
                        $this->putS3Obj($typeAmbient, $keyNameAux, $photo->basePath());
                        //se borra foto temporal
                        unlink($photo->basePath());
                    }
                }
                return $this->createResp($respBase, ($respBase) ? 'Guardado' : 'Error', $keyName);
            }
            return [
                'msg' => 'Error',
                'url' => $keyName
            ];
        } catch (AwsException $e) {
            throw new Exception('(fn => putPhotoS3, Error => ' . $e->getMessage() . ')');
        }
    }

    public function putFileS3($file, $typeAmbient, $urlBase){
        try {
            $keyName = "";
            if (env('APP_ENV', 'local') != 'local') {
                //validación archivo 
                $this->validaArchivo($file);
                $filename = $this->cleanFileName($file) . '.' . File::extension($file->getClientOriginalName());
                $keyName = $this->clearUrl($urlBase . '/' . $filename);
                //dd($keyName,$this->buckets[$typeAmbient][env('APP_ENV')],$file);
                $result = $this->putS3Obj($typeAmbient, $keyName, $file->path());
                return $this->createResp($result, ($result) ? 'Guardado' : 'Error', $keyName);
            }
            return [
                'msg' => 'Error',
                'url' => $keyName
            ];
        } catch (AwsException $e) {
            throw new Exception('(fn => putFileS3, Error => ' . $e->getMessage() . ')');
        }
    }

    private function putS3Obj($typeAmbient, $keyName, $file){
        $this->create_s3_client();
        $result = $this->s3->putObject([
            'Bucket'     => $this->buckets[$typeAmbient][EnvHelper::get('APP_ENV')],
            'Key'        => $this->paths[$typeAmbient][EnvHelper::get('APP_ENV')] . $keyName,
            'SourceFile' => $file,
        ]);
        return ($result['@metadata']['statusCode'] == 200);
    }

    //validación archivo imagen
    private function validaImg($file)
    {
        $fileBaseName = $file->getClientOriginalName();
        $extension = pathinfo($fileBaseName, PATHINFO_EXTENSION);

        if (!preg_match("/(gif|jpe?g|png|bmp|ico)$/i", $extension)) {
            throw new Exception('El archivo enviado no es una imagen valida');
        }
    }

    //validación archivo imagen
    private function validaArchivo($file)
    {
        $fileBaseName = $file->getClientOriginalName();
        $extension = pathinfo($fileBaseName, PATHINFO_EXTENSION);

        if (!preg_match("/(dwg|dxf|pptx?|xlsx?|docx?|pdf|txt|rar|zip|gif|jpe?g|png|bmp|ico|sql)$/i", $extension)) {
            throw new Exception('El archivo enviado no es valido ');
        }
    }

    private function doesFileExistsS3(String $bucket, String $key)
    {
        return $this->s3->doesObjectExist($bucket, $key);
    }

    //Funcion que reescala una imagen
    private function resizeImage($img, $dim = [])
    {
        $fileBaseName = $img->getClientOriginalName();

        $extension = pathinfo($fileBaseName, PATHINFO_EXTENSION);

        if (preg_match("/(gif|jpe?g|png|bmp|ico)$/i", $extension)) {
            if (is_array($dim)) {
                $resp = ["img_base" => Image::make($img), "resize" => []];
                //redimensiones si existen
                for ($i = 0; $i < count($dim); $i++) {
                    $resp['resize'][$i] = [
                        "img"   =>  Image::make($img)->resize($dim[$i]->getWidth(), $dim[$i]->getHeight(), function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        }),
                        "width" => $dim[$i]->getWidth(),
                        "height" => $dim[$i]->getHeight()
                    ];
                }
                return $resp;
            }
        }
    }

    public function clearUrl(String $url)
    {
        $url = preg_replace("/%20/", ' ', $url);
        $url = preg_replace("/https:\/\/cl0int-s3-proveedores\.s3\.amazonaws\.com\//", '', $url);
        $url = preg_replace("/\?X-Amz-Content-Sha256=.+/", '', $url);
        $url = preg_replace("/\.\.\//", '/', $url);
        $url = preg_replace("/(\/\/\/|\/\/)/", '/', $url);
        $url = preg_replace("/^\//", '', $url);
        return $url;
    }

    private function createResp($flag, $msg, $url)
    {
        return [
            'flag' => $flag,
            'data'  => [
                'msg' => $msg,
                'url' => $url
            ]
        ];
    }

    function cleanFileName($file)
    {
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        return preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_',  $fileName));
    }
}
