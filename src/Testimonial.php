<?php
namespace Testimonials;

use DBAL\Database;

class Testimonial{
    protected $db;
    protected $errorNo;
    protected static $rootFolder;
    protected static $testimonialTable = 'testimonials';
    
    public $maxFileSize = 7240000; //7MB Approx
    public $imageSize = 0;
    public $allowedExt = array('gif', 'jpg', 'jpeg', 'png');
    public $imageFolder = 'testimonials/';
    public $thumbnailDir = 'thumbs/';
    public $sendEmail = true;
    public $tesimonialApprovalName;
    public $tesimonialApprovalEmail;
    
    public $minWidth = 200;
    public $minHeight = 150;

    public $imageInfo = false;
    public $createThumb = false;
    protected $autoApprove = false;
    
    /**
     * Constructor sets instance of the database connection
     * @param Database $db
     */
    public function __construct(Database $db){
        $this->db = $db;
        $this->setRootFolder(ROOT.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR);
    }
    
    /**
     * 
     * @param type $width
     * @param type $height
     * @return $this
     */
    public function setMinWidthHeight($width, $height){
        if(is_numeric($width) && is_numeric($height)){
            $this->minWidth = $width;
            $this->minHeight = $height;
        }
        return $this;
    }
    
    public function setMinWidth($width){
        $this->minWidth = $width;
        return $this;
    }
    
    public function getMinWidth(){
        return $this->minWidth;
    }
    
    public function setMinHeight($height){
        $this->minHeight = $height;
        return $this;
    }
    
    public function getMinHeight(){
        return $this->minHeight;
    }
    
    public function setMaxFileSize($bytes){
        $this->maxFileSize = $bytes;
        return $this;
    }
    
    public function getMaxFileSize(){
        return $this->maxFileSize;
    }
    
    public function setTestimonialTable($table){
        self::$testimonialTable = $table;
        return $this;
    }
    
    public function getTestimonialTable(){
        return self::$testimonialTable;
    }
    
    public function setRootFolder($folder){
        self::$rootFolder = $folder;
    }
    
    public function getRootFolder(){
        return self::$rootFolder;
    }
    
    public function setAutoApprove($autoApprove = true){
        $this->autoApprove = $autoApprove;
    }
    
    public function getAutoApprove(){
        return $this->autoApprove;
    }
    
    public function sendApproval($send = true, $email = false, $emailName = false){
        $this->sendEmail = (boolean)$send;
    }
    
    /**
     * Add a new testimonial to the database and upload any images
     * @param int $instructor This should be the instructor info
     * @param string $name The name of the pupil
     * @param string $testimonial The testimonial comments/ information from the pupil of instructor
     * @param string $heading The main heading to set for the testimonial
     * @param string $location The test centre location or area of the pupil
     * @param file $image This should be the $_FILES['image']
     * @param int $course The type of course taken (0 = Unknown, 1 = Weekly lessons, 2 = Test Booster, 3 = Midway Pass, 4 = Semi-Intensive, 5 = One Week Pass, 6 = Residential)
     * @param int $firstTime If it was a first time pass set to 1 
     * @param int $rating The rating given by the pupil
     * @param int $approved If the testimonial should be set to approved should be set to 1 else if needs to be approved set to 0
     * return boolean If the testimonial is added successfully will return true else returns false
     */
    public function addTestimonial($instructor, $name, $testimonial, $heading = NULL, $location = NULL, $image = NULL, $course = 0, $firstTime = 0, $rating = 5, $approved = 0){
        if($name && (!$image['name'] || $image['name'] && $this->uploadTestimonialImage($image))){
            if(!$heading){$heading = NULL;}
            if(!$location){$location = NULL;}
            if(!$image['name']){$image['name'] = NULL;}else{$image['name'] = $image['name'];}
            if($this->sendEmail){$this->sendApprovalEmail($name, $testimonial, $image, intval($course), intval($firstTime));}
            return $this->db->insert($this->getTestimonialTable(), array('fino' => $instructor, 'name' => $name, 'heading' => $heading, 'testimonial' => $testimonial, 'location' => $location, 'course_type' => intval($course), 'first_time_pass' => intval($firstTime), 'rating' => intval($rating), 'image' => $image['name'], 'width' => intval($this->imageInfo['width']), 'height' => intval($this->imageInfo['height']), 'approved' => intval($approved)));
        }
        return false;
    }
    
    /**
     * Updates a testimonial in the database and uploads any new images if required
     * @param int $fino The instructors unique fino
     * @param string $name The name of the pupil
     * @param string $testimonial The testimonial comments/ information from the pupil of instructor
     * @param string $heading The main heading to set for the testimonial
     * @param string $location The test centre location or area of the pupil
     * @param file $image This should be the $_FILES['image']
     * @param int $course The type of course taken (0 = Unknown, 1 = Weekly lessons, 2 = Test Booster, 3 = Midway Pass, 4 = Semi-Intensive, 5 = One Week Pass, 6 = Residential)
     * @param int $firstTime If it was a first time pass set to 1
     * @param dateTime $dateAdded The date the testimonial was added or change to alter the order of the testimonials
     * @param int $rating The rating given by the pupil
     * @return boolean
     */
    public function updateTestimonial($fino, $testimonialID, $name, $testimonial, $heading = NULL, $location = NULL, $image = NULL, $course = 0, $firstTime = 0, $dateAdded = NULL, $rating = 5){
        if($name && (!$image['name'] || $image['name'] && $this->uploadTestimonialImage($image))){
            if(!$heading){$heading = NULL;}
            if(!$location){$location = NULL;}
            if(!is_null($dateAdded) && !empty($dateAdded)){$updateDate = array('submitted' => date('Y-m-d H:i:s',strtotime($dateAdded)));}else{$updateDate = array();}
            if($image['name']){$imageArray = array('image' => $image['name'], 'width' => intval($this->imageInfo['width']), 'height' => intval($this->imageInfo['height']));}else{$imageArray = array();}
            return $this->db->update($this->getTestimonialTable(), array_merge(array('fino' => $fino, 'name' => $name, 'heading' => $heading, 'testimonial' => $testimonial, 'location' => $location, 'course_type' => intval($course), 'first_time_pass' => intval($firstTime), 'rating' => intval($rating)), $imageArray, $updateDate), array('id' => $testimonialID));
        }
        return false;
    }
    
    /**
     * Gets the testimonials for all instructors
     * @param int|boolean $id If you want to get an single testimonial include the unique id of that testimonial
     * @param int|boolean $status The status of the testimonials you wish to retrieve (0 = Pending, 1 = Approved, false = all);
     * @return array Returns an array of all / an individual testimonial(s) based on the input
     */
    public function getTestimonial($id = false, $status = false){
        if(is_numeric($id)){$where['id'] = $id; $num = 1;}else{$num = 0;}
        if(is_numeric($status)){$where['approved'] = intval($status);}
        return $this->db->selectAll($this->getTestimonialTable(), $where, '*', '', $num);
    }
    
    /**
     * Get the testimonials for a individual instructor
     * @param int $fino This should be the instructors fino
     * @param int $status The status of the testimonials to get (0 = pending, 1 = active)
     * @param int $max The maximum number of testimonials to return
     * @param boolean $random If the testimonials should be randomised set to true
     * @return array|boolean If the testimonials exists will return array else returns false
     */
    public function getInstructorTestimonials($fino, $status = 1, $max = 100, $random = false){
        if(is_numeric($status)){$approved = array('approved' => intval($status));}else{$approved = array();}
        if($random != false){$order = 'RAND()';}else{$order = array('submitted' => 'DESC');}
        if(is_numeric($fino)){
            return $this->db->selectAll($this->getTestimonialTable(), array_merge(array('fino' => $fino), $approved), '*', $order, intval($max));
        }
        return false;
    }
    
    /**
     * Delete the testimonial and associated images from the server and database
     * @param int $testimonialID This should be the unique ID of the image in the database
     * @return boolean Returns true if the image is deleted else returns false
     */
    public function deleteTestimonial($testimonialID){
        if(is_numeric($testimonialID)){
            $testimonialInfo = $this->db->select($this->getTestimonialTable(), array('id' => $testimonialID));
            if($testimonialInfo['image']){$this->deleteTestimonialImage($testimonialInfo['image']);}
            return $this->db->delete($this->getTestimonialTable(), array('id' => $testimonialID));
        }
        return false;
    }
    
    /**
     * Change the approved status of a testimonial
     * @param int $testimonialID This should be the testimonials unique ID number
     * @param int $approve This should be set to 1 or 0
     * @return boolean Returns true if successfully updated else returns false
     */
    public function approveTestimonial($testimonialID, $approve = 1){
        if(is_numeric($testimonialID) && is_numeric($approve)){
            return $this->db->update($this->getTestimonialTable(), array('approved' => $approve), array('id' => $testimonialID));
        }
        return false;
    }
    
    /**
     * Upload an image to the server
     * @param file $image This should be the $_FILES['image']
     * @return boolean Returns true if image uploaded successfully else returns false
     */
    protected function uploadTestimonialImage($image){
        if($image['name']){
            $this->checkDirectoryExists($this->rootFolder.$this->imageFolder);
            if($this->isImageReal($image) && $this->imageExtCheck($image) && $this->imageSizeCheck($image) && $this->sizeGreaterThan($image) && !$this->imageExist($image)){
                if(move_uploaded_file($image['tmp_name'], $this->rootFolder.$this->imageFolder.basename($image['name']))){
                    if($this->createThumb == true){$this->createImageThumb($image);}
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Create a thumbnail for the given image
     * @param file $image This should be the $_FILES['image']
     */
    protected function createImageThumb($image){
        $max_thumb_width = 200;
        $new_height = intval($this->imageInfo['height'] * ($max_thumb_width / $this->imageInfo['width']));
        if($this->imageInfo['type'] == 1){
            $imgt = "ImageGIF";
            $imgcreatefrom = "ImageCreateFromGIF";
        }
        if($this->imageInfo['type'] == 2){
            $imgt = "ImageJPEG";
            $imgcreatefrom = "ImageCreateFromJPEG";
        }
        if($this->imageInfo['type'] == 3){
            $imgt = "ImagePNG";
            $imgcreatefrom = "ImageCreateFromPNG";
        }
        if($imgt){
            $old_image = $imgcreatefrom($this->rootFolder.$this->imageFolder.basename($image['name']));
            imagealphablending($old_image, true);
            $new_image = imagecreatetruecolor($max_thumb_width, $new_height);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            imagecopyresized($new_image, $old_image, 0, 0, 0, 0, $max_thumb_width, $new_height, $this->imageInfo['width'], $this->imageInfo['height']);
            $imgt($new_image, $this->rootFolder.$this->imageFolder.$this->thumbnailDir.$image['name']);
        }
    }
    
    /**
     * Delete and image from the server
     * @param string $image This should be the image name with extension
     * @return boolean Returns true if deleted else returns false
     */
    protected function deleteTestimonialImage($image){
        if(file_exists($this->rootFolder.$this->imageFolder.$image["name"])){
            unlink($this->imageLocation.$image["name"]);
            unlink($this->imageLocation.$this->thumbnailDir.$image["name"]);
            return true;
        }
        return false;
    }
    
    /**
     * Checks to see if the image is a real image
     * @param file $image This should be the $_FILES['image']
     * @return string|boolean If the image is real the mime type will be returned else will return false
     */
    protected function isImageReal($image){
        list($this->imageInfo['width'], $this->imageInfo['height'], $this->imageInfo['type'], $this->imageInfo['attr']) = getimagesize($image["tmp_name"]);
        if($this->imageInfo !== false) {
           return $this->imageInfo['type'];
        }
        $this->errorNo = 1;
        return false;
    }
    
    /**
     * Checks to see if the image is within the allowed size limit
     * @param file $image This should be the $_FILES['image']
     * @return boolean Returns true if allowed size else returns false
     */
    protected function imageSizeCheck($image){
        if($image['size'] > $this->maxFileSize){
            $this->imageSize = $image['size'];
            return false;
        }
        $this->errorNo = 2;
        return true;
    }
    
    /**
     * Checks to see if the image has one of the allowed extensions
     * @param file $image This should be the $_FILES['image']
     * @return boolean Returns true if allowed else returns false
     */
    protected function imageExtCheck($image){
        $fileType = strtolower(pathinfo($this->rootFolder.$this->imageFolder.$image['name'], PATHINFO_EXTENSION));
        if(in_array($fileType, $this->allowedExt)){
            return true;
        }
        $this->errorNo = 3;
        return false;
    }
    
    /**
     * Checks to see if a image with the same name already exists on the server
     * @param file $image This should be the $_FILES['image']
     * @return boolean Returns true if image exists else return false
     */
    protected function imageExist($image){
        if(file_exists($this->rootFolder.$this->imageFolder.basename($image["name"]))){
            $this->errorNo = 4;
            return true;
        }
        return false;
    }
    
    /**
     * Makes sure that the image dimensions are greater or equal to the minimum dimensions
     * @param file $image This should be the $_FILES['image']
     * @return boolean Returns true if the image dimensions are greater or equal else returns false
     */
    protected function sizeGreaterThan($image) {
        list($this->imageInfo['width'], $this->imageInfo['height'], $this->imageInfo['type'], $this->imageInfo['attr']) = getimagesize($image["tmp_name"]);
        if($this->imageInfo['width'] >= $this->minWidth && $this->imageInfo['height'] >= $this->minHeight){
            return true;
        }
        $this->errorNo = 5;
        return false;
    }
    
    /**
     * Checks to see if a directory exists if not it creates it
     * @param string $directory The location of the directory
     */
    protected function checkDirectoryExists($directory){
        if(!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
    }
    
    /**
     * Returns the error message for image upload problems
     * @return string Returns the error message
     */
    public function getErrorMsg(){
        if($this->errorNo == 1){return 'The image is not a valid image format';}
        elseif($this->errorNo == 2){return 'The image is too large to upload please make sure your image is smaller than 5MB in size your image is '.$this->imageSize;}
        elseif($this->errorNo == 3){return 'The image is not allowed! Please make sure your image has of of the allowed extensions';}
        elseif($this->errorNo == 4){return 'The image with this name has already been uploaded or already exists on our server!';}
        elseif($this->errorNo == 5){return 'The image dimensions are too small. It must be greater than 200px in width and 150px in height';}
        else{return 'An error occured while adding the testimonial. Please try again!';}
    }
    
    /**
     * Send an email to head office to make note of the new testimonial submission
     * @param string $name The name of the student
     * @param string $testimonial The information given in the testimonial
     * @param file $image The $_FILE['image'] information
     * @param int $course The course ID
     * @param int $firstTime If it is a first time pass or not
     */
    protected function sendApprovalEmail($name, $testimonial, $image, $course, $firstTime, $fino, $name, $email){
        if($image['name']){$attachment = array($this->rootFolder.$this->imageFolder.basename($image['name']), $image['name'], 'base64', $this->imageInfo['type']); $imageAttached = 'Yes';}else{$imageAttached = 'No';}
        include(EMAIL_PATH.'testimonialSubmission.php');
        if($firstTime == '1'){$firstTime = 'Yes';}else{$firstTime = 'No';}
        $html = sprintf($emailhtml, $this->tesimonialApprovalName, $this->instructor['fino'], $this->instructor['ldiname'], $name, $testimonial, $imageAttached, $course, $firstTime);
        sendEmail($this->tesimonialApprovalEmail, sprintf($emailsubject, $this->instructor['fino'], $name), convertHTMLtoPlain($plain), htmlEmail($html), 'submissions@ldcwebhost5.co.uk', 'LDC Testimonial', '', $this->instructor['email'], $attachment);
    }
}