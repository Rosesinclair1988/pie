<?php

class Eternal_Venedor_Block_Core_Messages extends Mage_Core_Block_Messages
{
    /**
     * Retrieve messages in HTML format grouped by type
     *
     * @param   string $type
     * @return  string
     */
    public function getGroupedHtml()
    {
        if(Mage::app()->getStore()->isAdmin())
            return parent::getGroupedHtml();
            
        $types = array(
            Mage_Core_Model_Message::ERROR,
            Mage_Core_Model_Message::WARNING,
            Mage_Core_Model_Message::NOTICE,
            Mage_Core_Model_Message::SUCCESS
        );
        $html = '';
        foreach ($types as $type) {
            if ( $messages = $this->getMessages($type) ) {
                if ( !$html ) {
                    $html .= '<div class="messages">';
                }
                $alert = 'alert';
                switch ($type) {
                    case 'error': 
                        $alert .= ' alert-danger'; 
                        break;
                    case 'success': 
                        $alert .= ' alert-success';
                        break;
                    case 'note': 
                    case 'notice': 
                        $alert .= ' alert-info';
                        break;
                    default:
                        $alert .= '';
                        break;
                }
                $html .= '<div class="'. $alert . ' ' . $type . '-msg"><button type="button" class="close" data-dismiss="alert">&times;</button>';
                $html .= '<' . $this->_messagesFirstLevelTagName . '>';

                foreach ( $messages as $message ) {
                    $html.= '<' . $this->_messagesSecondLevelTagName . '>';
                    $html.= '<' . $this->_messagesContentWrapperTagName . '>';
                    $html.= ($this->_escapeMessageFlag) ? $this->htmlEscape($message->getText()) : $message->getText();
                    $html.= '</' . $this->_messagesContentWrapperTagName . '>';
                    $html.= '</' . $this->_messagesSecondLevelTagName . '>';
                }
                $html .= '</' . $this->_messagesFirstLevelTagName . '>';
                $html .= '</div>';
            }
        }
        if ( $html) {
            $html .= '</div>';
        }
        return $html;
    }
}
