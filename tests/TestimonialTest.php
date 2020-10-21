<?php
namespace Testimonials\Tests;

use DBAL\Database;
use Testimonials\Testimonial;
use PHPUnit\Framework\TestCase;

class TestimonialTest extends TestCase
{
    protected $db;
    protected $testimonial;
    
    public function setUp(): void
    {
        $this->db = new Database($GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD'], $GLOBALS['DB_DBNAME']);
        $this->testimonial = new Testimonial($this->db);
    }
    
    public function tearDown(): void
    {
        unset($this->testimonial);
    }
    
    public function testExample()
    {
        $this->markTestIncomplete('Test not yet complete');
    }
}
