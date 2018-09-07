<?php
namespace Testimonials;

use DBAL\Database;
use ImgUpload\ImageUpload;

class Testimonial extends ImageUpload{
    protected $db;
    protected static $testimonialTable = 'testimonials';
    
    protected $autoApprove = false;
    
    public $sendEmail = true;
    protected $emailTo = 'User';
    protected $emailToAdd = 'me@myemail.com';
    protected $emailFrom = 'testimonials@example.co.uk';
    protected $emailName = 'Testimonial Submission';
    protected $replyTo = '';
    
    protected $emailPath = 'email'.DIRECTORY_SEPARATOR;
    
    /**
     * Constructor sets instance of the database connection
     * @param Database $db
     */
    public function __construct(Database $db){
        parent::__construct();
        $this->db = $db;
    }
    
    /**
     * Set the database table name to a custom value
     * @param string $table This should be the table name of where you want to store the testimonials
     * @return $this
     */
    public function setTestimonialTable($table){
        self::$testimonialTable = $table;
        return $this;
    }
    
    /**
     * Returns the table name where the testimonials can be found
     * @return string This will the be database table where the testimonials are
     */
    public function getTestimonialTable(){
        return self::$testimonialTable;
    }
    
    /**
     * Sets the auto approve status to the given value
     * @param boolean $autoApprove If you wish to auto approve testimonials set to true else set to false
     * @return $this
     */
    public function setAutoApprove($autoApprove = true){
        $this->autoApprove = (bool)$autoApprove;
        return $this;
    }
    
    /**
     * Gets the auto approval status 
     * @return boolean If the testimonials are to be automatically approved should be true else will be false
     */
    public function getAutoApprove(){
        return $this->autoApprove;
    }
    
    /**
     * Sets the location that the script should look for the testimonial email
     * @param string $path This should be the path where the email file is located
     * @return $this
     */
    public function setEmailPath($path){
        $this->emailPath = $path;
        return $this;
    }
    
    /**
     * Returns the email path where the template email can be found
     * @return string This is the email path
     */
    public function getEmailPath(){
        return $this->emailPath;
    }
    
    /**
     * Sets the details to send the approval emails to a certain email address
     * @param string $emailToAdd This should be the email address you are sending the emails to
     * @param string $emailTo This should be the name of the person you are sending emails to
     * @param string $emailFromAdd This should be the email address where the email should be shown as coming from
     * @param string $emailFrom This should be the name set as the person sending the emails
     * @param string $replyTo This should be the email reply to address
     * @param boolean $send If you want to send an approval email set to true else set as false
     */
    public function sendApproval($emailToAdd, $emailTo, $emailFromAdd, $emailFrom, $replyTo = '', $send = true){
        $this->sendEmail = (boolean)$send;
        if(filter_var($emailToAdd, FILTER_VALIDATE_EMAIL)){
            $this->emailToAdd = $emailToAdd;
        }
        $this->emailTo = $emailTo;
        if(filter_var($emailToAdd, FILTER_VALIDATE_EMAIL)){
            $this->emailFrom = $emailFromAdd;
        }
        $this->emailName = $emailFrom;
        $this->replyTo = $replyTo;
    }
    
    /**
     * Add a new testimonial to the database and upload any images
     * @param string $name The name of the person giving the testimonial
     * @param string $testimonial The testimonial comments/ information from the pupil of instructor
     * @param string $heading The main heading to set for the testimonial
     * @param file $image This should be the $_FILES['image']
     * @param array $additionalInfo Any additional information as an array that wants adding to the database as array('fieldname' => 'value')
     * @param string|false $submittedBy This should be the name of the person submitting the testimonial for user systems if other than the person who gave the testimonial
     * return boolean If the testimonial is added successfully will return true else returns false
     */
    public function addTestimonial($name, $testimonial, $heading = '', $image = false, $additionalInfo = [], $submittedBy = false) {
        if($image['name']){$image['name'] = $this->makeSafeFileName($image['name']);}
        if($name && (!$image['name'] || $image['name'] && $this->uploadImage($image))){
            if(empty($heading)){$heading = NULL;}
            if(!$image['name']){$image['name'] = NULL;}else{$image['name'] = $image['name'];}
            if($this->sendEmail === true){$this->sendApprovalEmail($name, $testimonial, $heading, $image, $additionalInfo, $submittedBy);}
            return $this->db->insert($this->getTestimonialTable(), array_merge(
                array('name' => $name, 'heading' => $heading, 'testimonial' => $testimonial, 'image' => $image['name'], 'width' => intval($this->imageInfo['width']), 'height' => intval($this->imageInfo['height'])),
                $additionalInfo,
                array('approved' => ($this->autoApprove === true ? 1 : 0))
            ));
        }
        return false;
    }
    
    /**
     * Updates a testimonial in the database and uploads any new images if required
     * @param int $testimonialID The unique testimonial ID that you are updating
     * @param string $name The name of the person giving the testimonial
     * @param string $testimonial The testimonial comments/ information from the pupil of instructor
     * @param string $heading The main heading to set for the testimonial
     * @param file $image This should be the $_FILES['image']
     * @param array $additionalInfo Any additional information as an array that wants adding to the database as array('fieldname' => 'value')
     * @param dateTime $dateAdded The date the testimonial was added or change to alter the order of the testimonials
     * @return boolean
     */
    public function updateTestimonial($testimonialID, $name, $testimonial, $heading = '', $image = NULL, $additionalInfo = [], $dateAdded = NULL) {
        if($image['name']){$image['name'] = $this->makeSafeFileName($image['name']);}
        if($name && (!$image['name'] || $image['name'] && $this->uploadImage($image))){
            if(empty($heading)){$heading = NULL;}
            if(!is_null($dateAdded) && !empty($dateAdded)){$updateDate = array('submitted' => date('Y-m-d H:i:s', strtotime($dateAdded)));}else{$updateDate = array();}
            if($image['name']){$imageArray = array('image' => $image['name'], 'width' => intval($this->imageInfo['width']), 'height' => intval($this->imageInfo['height']));}else{$imageArray = array();}
            return $this->db->update($this->getTestimonialTable(), array_merge(array('name' => $name, 'heading' => $heading, 'testimonial' => $testimonial), $imageArray, $updateDate, $additionalInfo), array('id' => $testimonialID));
        }
        return false;
    }
    
    /**
     * Gets the testimonials
     * @param int|boolean $id If you want to get an single testimonial include the unique id of that testimonial
     * @param int|boolean $status The status of the testimonials you wish to retrieve (0 = Pending, 1 = Approved, false = all);
     * @param array $search If you want to search particular fields place th field name and value in an array
     * @param boolean $random If you want a random order set to true else set as false
     * @return array Returns an array of all / an individual testimonial(s) based on the input
     */
    public function getTestimonial($id = false, $status = false, $search = [], $random = false){
        $where = array();
        if(is_numeric($id)){$where['id'] = $id; $num = 1;}else{$num = 0;}
        if(is_numeric($status)){$where['approved'] = intval($status);}
        if(!empty($search)){
            foreach($search as $var => $value){
                $where[$var] = $value;
            }
        }
        if($random !== false){$order = 'RAND()';}else{$order = array('submitted' => 'DESC');}
        return $this->db->selectAll($this->getTestimonialTable(), $where, '*', $order, $num);
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
     * Send an email to make note of the new testimonial submission
     * @param string $name The name of the person giving the testimonial
     * @param string $testimonial The testimonial comments/ information from the pupil of instructor
     * @param string $heading The main heading to set for the testimonial
     * @param file $image This should be the $_FILES['image']
     * @param array $additionalInfo Any additional information as an array that wants adding to the database as array('fieldname' => 'value')
     * @param string|false $submittedBy This should be the name of the person submitting the testimonial for user systems if other than the person who gave the testimonial
     * return boolean If the testimonial is added successfully will return true else returns false
     */
    protected function sendApprovalEmail($name, $testimonial, $heading, $image, $additionalInfo, $submittedBy = false){
        $attachment = array();
        if($image['name']){$attachment[] = array($this->getRootFolder().$this->getImageFolder().basename($image['name']), $image['name']); $imageAttached = 'Yes';}
        else{$imageAttached = 'No';}
        include($this->getEmailPath().'testimonialSubmission.php');
        $additional = '';
        foreach($additionalInfo as $k => $value){
            $additional.= "<p><strong>".$k.":</strong> ".$value."</p>\r\n"; 
        }
        $html = sprintf($emailhtml, $this->emailTo, ($submittedBy ? $submittedBy : $name), $name, $heading, $testimonial, $additional, $imageAttached);
        return sendEmail($this->emailToAdd, sprintf($emailsubject, ($submittedBy ? $submittedBy : $name)), convertHTMLtoPlain($html), $html, $this->emailFrom, $this->emailName, '', $this->replyTo, $attachment);
    }
    
    /**
     * Makes the file name safe for URL
     * @param string $name This should be the original file name
     * @param boolean $addDateTime If you want to add a date/time stamp to filename
     * @return string Returns the new file name
     */
    private function makeSafeFileName($name, $addDateTime = false){
        return ($addDateTime === true ? date('YmdHis').'-' : '').strtolower(str_replace(' ', '', preg_replace("/[^A-Za-z0-9 ]/", '', $name)));
    }
}