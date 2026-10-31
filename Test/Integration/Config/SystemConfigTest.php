<?php declare(strict_types=1);

namespace Yireo\CspUtilities\Test\Integration\Config;

use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\CspUtilities\Config\Source\InlineScriptOptions;

/**
 * @magentoAppArea adminhtml
 */
class SystemConfigTest extends TestCase
{
    public function testInlineScriptModeField()
    {
        $field = $this->getStructure()->getElementByConfigPath('csp_utilities/settings/inline_script_mode');
        $this->assertInstanceOf(Field::class, $field);
        $this->assertSame('select', $field->getType());
        $this->assertTrue($field->hasSourceModel());
        $this->assertSame(InlineScriptOptions::class, $field->getData()['source_model']);
    }

    public function testSectionUsesAclResource()
    {
        $section = $this->getStructure()->getElement('csp_utilities');
        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame('Yireo_CspUtilities::config', $section->getAttribute('resource'));
    }

    private function getStructure(): Structure
    {
        return ObjectManager::getInstance()->get(Structure::class);
    }
}
