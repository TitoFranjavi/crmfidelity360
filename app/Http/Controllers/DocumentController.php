<?php

namespace App\Http\Controllers;

define("RAIZ", "1ltTVPpoX7mXl0UbyeVOrywFDSPpchghW");//Carpeta raíz --> https://drive.google.com/drive/folders/1dgpmuEJ-Yziz2lLOoakxMOGv3y4Y8UqX
putenv('GOOGLE_APPLICATION_CREDENTIALS='. $_SERVER["DOCUMENT_ROOT"] . "/crm-asercord-1a5a82d82b3f.json");


use App\Helpers\APIHelper;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;

class DocumentController extends Controller
{

    //función para obtener contenido de directorio de google drive
    public static function listFolder(Request $request)
    {
        $userSubdomain = $request['userSubdomain'];
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain['drive']['json_credentials_name'];

        $id = $request['id'];
        $isSharedDrive = $userSubdomain['drive']['sharedDrive'] ?? false;

        $service = APIHelper::getInstance($jsonFullPath);
        $results = [];

        $params = [
            'q' => "'$id' in parents and trashed = false",
            'spaces' => 'drive',
            'fields' => 'nextPageToken, files(id, name, mimeType, thumbnailLink, modifiedTime)',
        ];

        if ($isSharedDrive) {
            $params['supportsAllDrives'] = true;
            $params['includeItemsFromAllDrives'] = true;
            $params['corpora'] = 'drive';
            $params['driveId'] = $userSubdomain['drive']['driveId'];
        }

        $response = $service->files->listFiles($params);

        foreach ($response->getFiles() as $file) {
            $results[] = [
                "id" => $file->getId(),
                "name" => $file->getName(),
                "type" => $file->getMimeType() === 'application/vnd.google-apps.folder'
                    ? 'carpeta'
                    : $file->getMimeType(),
                "miniature" => $file->getThumbnailLink(),
                'lastModifiedTime' => $file->getModifiedTime()
            ];
        }

        return response()->json(['results' => $results], 200);
    }

    //función para crear una carpeta
    public static function createFolder(Request $request){

        $userSubdomain = $request['userSubdomain'];
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain['drive']['json_credentials_name'];

        $name = $request['name'];
        $parentFolderKey = $request['parentFolder'];
        $isSharedDrive = $userSubdomain['drive']['sharedDrive'] ?? false;
        $service = APIHelper::getInstance($jsonFullPath);

        try{
            $folder = new Drive\DriveFile();
            $folder->setName($name);
            $folder->setParents([$parentFolderKey]);
            $folder->setDescription('Directorio creado desde crm');
            $folder->setMimeType('application/vnd.google-apps.folder');

            $params = ['fields' => 'id'];
            if ($isSharedDrive) {
                $params['supportsAllDrives'] = true;
            }

            $service->files->create($folder, $params);

            return response()->json(['message' => 'La carpeta ha sido creada correctamente'], 200);

        }catch(\Google_Service_Exception $error){
            return response()->json(['error' => $error->getMessage()], 400);
        }
    }


    //función para crear un archivo
    public static function createFile(Request $request){

        $userSubdomain = json_decode($request['userSubdomain']);
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain->drive->json_credentials_name;

        $files = $request['files'];
        $parentFolder = $request['parentFolder'];
        $isSharedDrive = $userSubdomain->drive->sharedDrive ?? false;
        $service = APIHelper::getInstance($jsonFullPath);

        foreach ($files as $fileNow){
            try{
                $file = new Drive\DriveFile();
                $file->setName($fileNow->getClientOriginalName());
                $file->setParents([$parentFolder]);
                $file->setDescription('Archivo creado desde crm');
                $mimeType = self::mime_type($fileNow->getClientOriginalName());
                $file->setMimeType($mimeType);

                $params = [
                    'data' => file_get_contents($fileNow),
                    'mimeType' => $mimeType,
                    'uploadType' => 'media',
                ];
                if ($isSharedDrive) {
                    $params['supportsAllDrives'] = true;
                }

                $service->files->create($file, $params);

            }catch(\Google_Service_Exception $error){
                return response()->json(['error' => $error->getMessage()], 400);
            }
        }

        return response()->json(['message' => 'Los archivos han sido creados correctamente'], 200);
    }


    //función para actualizar un archivo o carpeta
    public static function updateFileOrFolder(Request $request){

        $userSubdomain = $request['userSubdomain'];
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain['drive']['json_credentials_name'];

        $id = $request['id'];
        $name = $request['name'];
        $isSharedDrive = $userSubdomain['drive']['sharedDrive'] ?? false;
        $service = APIHelper::getInstance($jsonFullPath);

        try{
            $fileOrFolder = new Drive\DriveFile();
            $fileOrFolder->setName($name);

            $params = ['fields' => 'id, name'];
            if ($isSharedDrive) {
                $params['supportsAllDrives'] = true;
            }

            $updatedFile = $service->files->update($id, $fileOrFolder, $params);

            return response()->json(['name' => $updatedFile->getName()], 200);

        }catch(\Google_Service_Exception $error){
            return response()->json(['error' => $error->getMessage()], 400);
        }
    }


    //función para eliminar un archivo o carpeta
    public static function deleteFileOrFolder(Request $request){

        $userSubdomain = $request['userSubdomain'];
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain['drive']['json_credentials_name'];

        $id = $request['id'];
        $isSharedDrive = $userSubdomain['drive']['sharedDrive'] ?? false;
        $service = APIHelper::getInstance($jsonFullPath);

        try {
            $params = [];
            if ($isSharedDrive) {
                $params['supportsAllDrives'] = true;
            }

            $service->files->delete($id, $params);

            return response()->json(['message' => 'El archivo ha sido eliminado correctamente'], 200);

        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 400);
        }
    }


    //función para descargar un archivo
    public static function downloadFile(Request $request){

        $userSubdomain = $request['userSubdomain'];
        $jsonFullPath = $_SERVER["DOCUMENT_ROOT"] . '/drive_credentials/' . $userSubdomain['drive']['json_credentials_name'];

        $id = $request['id'];
        $isSharedDrive = $userSubdomain['drive']['sharedDrive'] ?? false;
        $service = APIHelper::getInstance($jsonFullPath);

        $params = ['alt' => 'media'];
        if ($isSharedDrive) {
            $params['supportsAllDrives'] = true;
        }

        $response = $service->files->get($id, $params);
        $content = $response->getBody()->getContents();

        return response($content, 200)->header('Content-Type', $response->getHeaderLine('Content-Type'));
    }

    static public function mime_type($filename) {

        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'json' => array('application/json', 'text/json'),
            'xml' => 'text/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            'hqx' => 'application/mac-binhex40',
            'cpt' => 'application/mac-compactpro',
            'csv' => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
            'bin' => 'application/macbinary',
            'dms' => 'application/octet-stream',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'exe' => array('application/octet-stream', 'application/x-msdownload'),
            'class' => 'application/octet-stream',
            'so' => 'application/octet-stream',
            'sea' => 'application/octet-stream',
            'dll' => 'application/octet-stream',
            'oda' => 'application/oda',
            'ps' => 'application/postscript',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'mif' => 'application/vnd.mif',
            'wbxml' => 'application/wbxml',
            'wmlc' => 'application/wmlc',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'dxr' => 'application/x-director',
            'dvi' => 'application/x-dvi',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'php' => 'application/x-httpd-php',
            'php4' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps' => 'application/x-httpd-php-source',
            'js' => array('application/javascript', 'application/x-javascript'),
            'sit' => 'application/x-stuffit',
            'tar' => 'application/x-tar',
            'tgz' => array('application/x-tar', 'application/x-gzip-compressed'),
            'xhtml' => 'application/xhtml+xml',
            'xht' => 'application/xhtml+xml',
            'bmp' => array('image/bmp', 'image/x-windows-bmp'),
            'gif' => 'image/gif',
            'jpeg' => array('image/jpeg', 'image/pjpeg'),
            'jpg' => array('image/jpeg', 'image/pjpeg'),
            'jpe' => array('image/jpeg', 'image/pjpeg'),
            'png' => array('image/png', 'image/x-png'),
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'shtml' => 'text/html',
            'text' => 'text/plain',
            'log' => array('text/plain', 'text/x-log'),
            'rtx' => 'text/richtext',
            'rtf' => 'text/rtf',
            'xsl' => 'text/xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'word' => array('application/msword', 'application/octet-stream'),
            'xl' => 'application/excel',
            'eml' => 'message/rfc822',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
            'rar' => 'application/x-rar-compressed',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mpga' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
            'aif' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'rv' => 'video/vnd.rn-realvideo',
            'wav' => array('audio/x-wav', 'audio/wave', 'audio/wav'),
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => array('image/vnd.adobe.photoshop', 'application/x-photoshop'),
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
            'ppt' => array('application/powerpoint', 'application/vnd.ms-powerpoint'),

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = explode('.', $filename);
        $ext = strtolower(end($ext));

        if (array_key_exists($ext, $mime_types)) {
            return (is_array($mime_types[$ext])) ? $mime_types[$ext][0] : $mime_types[$ext];
        } else if (function_exists('finfo_open')) {
            if(file_exists($filename)) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mimetype = finfo_file($finfo, $filename);
                finfo_close($finfo);
                return $mimetype;
            }
        }

        return 'application/octet-stream';
    }

}
