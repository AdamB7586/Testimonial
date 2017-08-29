<?php
namespace Testimonials\Tests;

use DBAL\Database;
use Testimonials\Testimonial;
use PHPUnit\Framework\TestCase;

class TestimonialTest extends TestCase{
    
    protected $testimonial;
    
    public function setUp() {
        $this->testimonial = new Testimonial(new Database('127.0.0.1', 'root', '', 'test_db'));
    }
    
    public function tearDown() {
        unset($this->testimonial);
    }
    
    public function testExample(){
        $this->markTestIncomplete('Test not yet complete');
    }
}
