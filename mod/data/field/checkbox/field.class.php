<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999-onwards Moodle Pty Ltd  http://moodle.com          //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

class data_field_checkbox extends data_field_base {

    var $type = 'checkbox';
    /**
     * priority for globalsearch indexing
     *
     * @var int
     */
    protected static $priority = self::LOW_PRIORITY;

    public function supports_preview(): bool {
        return true;
    }

    public function get_data_content_preview(int $recordid): stdClass {
        $options = explode("\n", $this->field->param1);
        $options = array_map('trim', $options);
        $selected = $options[$recordid % count($options)];
        return (object)[
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            'content' => $selected,
            'content1' => null,
            'content2' => null,
            'content3' => null,
            'content4' => null,
        ];
    }

    function display_add_field($recordid = 0, $formdata = null) {
        global $DB, $OUTPUT, $SESSION;

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id;
            $content = $formdata->$fieldname ?? [];
        } else if ($recordid) {
            $content = $DB->get_field('data_content', 'content', ['fieldid' => $this->field->id, 'recordid' => $recordid]);
            $content = explode('##', $content);
        } else {
            $content = [];
        }

        $str = '<div title="' . s($this->field->description) . '">';
        $str .= '<fieldset><legend><span class="accesshide">'.$this->field->name;
        if ($this->field->required) {
            $str .= '$nbsp;' . get_string('requiredelement', 'form');
            $str .= '</span></legend>';
            $image = $OUTPUT->pix_icon('req', get_string('requiredelement', 'form'));
            $str .= html_writer::div($image, 'inline-req');
        } else {
            $str .= '</span></legend>';
        }

        $i = 0;
        foreach (explode("\n", $this->field->param1) as $checkbox) {
            $checkbox = trim($checkbox);
            if ($checkbox === '') {
                continue; // skip empty lines
            }
            $str .= '<input type="hidden" name="field_' . $this->field->id . '[]" value="" />';
            $str .= '<input type="checkbox" id="field_'.$this->field->id.'_'.$i.'" name="field_' . $this->field->id . '[]" ';
            $str .= 'value="' . s($checkbox) . '" class="mod-data-input mr-1" ';

            if (array_search($checkbox, $content) !== false) {
                $str .= 'checked />';
            } else {
                $str .= '/>';
            }

            //KTT CODE
            if ($this->field->name === "Needs1" || $this->field->name === "Needs2"){
                switch ($checkbox){
                    case "Comprehensive sexuality education":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Comprehensive sexuality education".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Educación integral en sexualidad".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Educação sexual abrangente".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Éducation sexuelle complète".'</label><br />';
                        }
                        break;
                    case "Community-based channels":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Community-based channels".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canales comunitarios".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canais de base comunitária".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canaux communautaires".'</label><br />';
                        }
                        break;
                    case "Humanitarian responses":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Humanitarian responses".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Respuestas Humanitarias".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Respostas humanitárias".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Réponses humanitaires".'</label><br />';
                        }
                        break;
                    case "General SRH services":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."General SRH services".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Servicios generales de SSR".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Serviços gerais de SSR".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Services généraux de santé sexuelle et reproductive".'</label><br />';
                        }
                        break;
                    case "Safe and/or legal abortion":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Safe and/or legal abortion".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Aborto seguro y/o legal".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Aborto seguro e/ou legal".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Avortement sûr et/ou légal".'</label><br />';
                        }
                        break;
                    case "Contraception":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Contraception".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Anticoncepción".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Contracepção".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Contraception".'</label><br />';
                        }
                        break;
                    case "Sexual and gender-based violence":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Sexual and gender-based violence".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Violencia sexual y de género".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Violência sexual e baseada em gênero".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Violence sexuelle et sexiste".'</label><br />';
                        }
                        break;
                    case "Intersectional feminism and gender equity":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Intersectional feminism and gender equity".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Feminismo interseccional y equidad de género".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Feminismo interseccional e equidade de gênero".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Féminisme intersectionnel et équité entre les sexes".'</label><br />';
                        }
                        break;
                    case "Sexual and reproductive rights":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Sexual and reproductive rights".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Derechos sexuales y reproductivos".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Direitos sexuais e reprodutivos".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Droits sexuels et génésiques".'</label><br />';
                        }
                        break;
                    case "Digital health channels":
                        if ($SESSION->lang === 'en'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Digital health channels".'</label><br />';
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canales de salud digital".'</label><br />';
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canais de saúde digitais".'</label><br />';
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'."Canaux de santé numériques".'</label><br />';
                        }
                        break;
                }
            }else{
                $str .= '<label for="field_'.$this->field->id.'_'.$i.'">'.$checkbox.'</label><br />';
            }

            //$str .= '<label for="field_'.$this->field->id.'_'.$i.'">'.$checkbox.'</label><br />';
            $i++;
        }
        $str .= '</fieldset>';
        $str .= '</div>';
        return $str;
    }

    function display_search_field($value='') {
        global $CFG, $DB;

        if (is_array($value)) {
            $content = $value['checked'];
            $allrequired = $value['allrequired'] ? true : false;
        } else {
            $content = array();
            $allrequired = false;
        }

        $str = '';
        $found = false;
        $marginclass = ['class' => 'mr-1'];
        foreach (explode("\n",$this->field->param1) as $checkbox) {
            $checkbox = trim($checkbox);
            if (in_array($checkbox, $content)) {
                $str .= html_writer::checkbox('f_'.$this->field->id.'[]', s($checkbox), true, $checkbox, $marginclass);
            } else {
                $str .= html_writer::checkbox('f_'.$this->field->id.'[]', s($checkbox), false, $checkbox, $marginclass);
            }
            $str .= html_writer::empty_tag('br');
            $found = true;
        }
        if (!$found) {
            return '';
        }

        $requiredstr = get_string('selectedrequired', 'data');
        $str .= html_writer::checkbox('f_'.$this->field->id.'_allreq', null, $allrequired, $requiredstr, $marginclass);
        return $str;
    }

    public function parse_search_field($defaults = null) {
        $paramselected = 'f_'.$this->field->id;
        $paramallrequired = 'f_'.$this->field->id.'_allreq';

        if (empty($defaults[$paramselected])) { // One empty means the other ones are empty too.
            $defaults = array($paramselected => array(), $paramallrequired => 0);
        }

        $selected    = optional_param_array($paramselected, $defaults[$paramselected], PARAM_NOTAGS);
        $allrequired = optional_param($paramallrequired, $defaults[$paramallrequired], PARAM_BOOL);

        if (empty($selected)) {
            // no searching
            return '';
        }
        return array('checked'=>$selected, 'allrequired'=>$allrequired);
    }

    function generate_sql($tablealias, $value) {
        global $DB;

        static $i=0;
        $i++;
        $name = "df_checkbox_{$i}_";
        $params = array();
        $varcharcontent = $DB->sql_compare_text("{$tablealias}.content", 255);

        $allrequired = $value['allrequired'];
        $selected    = $value['checked'];

        if ($selected) {
            $conditions = array();
            $j=0;
            foreach ($selected as $sel) {
                $j++;
                $xname = $name.$j;
                $likesel = str_replace('%', '\%', $sel);
                $likeselsel = str_replace('_', '\_', $likesel);
                $conditions[] = "({$tablealias}.fieldid = {$this->field->id} AND ({$varcharcontent} = :{$xname}a
                                                                               OR {$tablealias}.content LIKE :{$xname}b
                                                                               OR {$tablealias}.content LIKE :{$xname}c
                                                                               OR {$tablealias}.content LIKE :{$xname}d))";
                $params[$xname.'a'] = $sel;
                $params[$xname.'b'] = "$likesel##%";
                $params[$xname.'c'] = "%##$likesel";
                $params[$xname.'d'] = "%##$likesel##%";
            }
            if ($allrequired) {
                return array(" (".implode(" AND ", $conditions).") ", $params);
            } else {
                return array(" (".implode(" OR ", $conditions).") ", $params);
            }
        } else {
            return array(" ", array());
        }
    }

    function update_content($recordid, $value, $name='') {
        global $DB;

        $content = new stdClass();
        $content->fieldid = $this->field->id;
        $content->recordid = $recordid;
        $content->content = $this->format_data_field_checkbox_content($value);

        if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
            $content->id = $oldcontent->id;
            return $DB->update_record('data_content', $content);
        } else {
            return $DB->insert_record('data_content', $content);
        }
    }

    function display_browse_field($recordid, $template) {
        global $SESSION;

        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->content)) {
            return '';
        }

        $options = explode("\n", $this->field->param1);
        $options = array_map('trim', $options);

        $contentarray = explode('##', $content->content);
        $str = '';
        foreach ($contentarray as $line) {
            if (!in_array($line, $options)) {
                // Hmm, looks like somebody edited the field definition.
                continue;
            }

            //KTT CODE
            if ($this->field->name === "Needs"){
                switch ($line){
                    case "Comprehensive sexuality education":
                        if ($SESSION->lang === 'en'){
                            $str .= "Comprehensive sexuality education" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Educación integral en sexualidad" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Educação sexual abrangente" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Éducation sexuelle complète" . "<br />\n";
                        }
                        break;
                    case "Community-based channels":
                        if ($SESSION->lang === 'en'){
                            $str .= "Community-based channels" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Canales de alcance comunitario" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Canais de alcance comunitário" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Canaux d'information communautaires" . "<br />\n";
                        }
                        break;
                    case "Humanitarian responses":
                        if ($SESSION->lang === 'en'){
                            $str .= "Humanitarian responses" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Respuestas Humanitarias" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Respostas humanitárias" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Réponses humanitaires" . "<br />\n";
                        }
                        break;
                    case "General SRH services":
                        if ($SESSION->lang === 'en'){
                            $str .= "General SRH services" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Servicios generales de SSR" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Serviços gerais de SSR" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Services généraux de santé sexuelle et reproductive" . "<br />\n";
                        }
                        break;
                    case "Safe and/or legal abortion":
                        if ($SESSION->lang === 'en'){
                            $str .= "Safe and/or legal abortion" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Aborto seguro y/o legal" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Aborto seguro e/ou legal" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Avortement sûr et/ou légal" . "<br />\n";
                        }
                        break;
                    case "Contraception":
                        if ($SESSION->lang === 'en'){
                            $str .= "Contraception" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Anticoncepción" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Contracepção" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Contraception" . "<br />\n";
                        }
                        break;
                    case "Sexual and gender-based violence":
                        if ($SESSION->lang === 'en'){
                            $str .= "Sexual and gender-based violence" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Violencia sexual y de género" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Violência sexual e baseada em gênero" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Violence sexuelle et sexiste" . "<br />\n";
                        }
                        break;
                    case "Intersectional feminism and gender equity":
                        if ($SESSION->lang === 'en'){
                            $str .= "Intersectional feminism and gender equity" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Feminismo interseccional y equidad de género" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Feminismo interseccional e equidade de gênero" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Féminisme intersectionnel et équité entre les sexes" . "<br />\n";
                        }
                        break;
                    case "Sexual and reproductive rights":
                        if ($SESSION->lang === 'en'){
                            $str .= "Sexual and reproductive rights" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Derechos sexuales y reproductivos" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Direitos sexuais e reprodutivos" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Droits sexuels et génésiques" . "<br />\n";
                        }
                        break;
                    case "Digital health channels":
                        if ($SESSION->lang === 'en'){
                            $str .= "Digital health channels" . "<br />\n";
                        }
                        if ($SESSION->lang === 'es'){
                            $str .= "Canales de salud" . "<br />\n";
                        }
                        if ($SESSION->lang === 'pt'){
                            $str .= "Canais de saúde" . "<br />\n";
                        }
                        if ($SESSION->lang === 'fr'){
                            $str .= "Canaux de santé" . "<br />\n";
                        }
                        break;
                }
            }else{
                $str .= $line . "<br />\n";
            }

            //$str .= $line . "<br />\n";
        }
        return $str;
    }

    function format_data_field_checkbox_content($content) {
        if (!is_array($content)) {
            return NULL;
        }
        $options = explode("\n", $this->field->param1);
        $options = array_map('trim', $options);

        $vals = array();
        foreach ($content as $key=>$val) {
            if ($key === 'xxx') {
                continue;
            }
            if (!in_array($val, $options)) {
                continue;

            }
            $vals[] = $val;
        }

        if (empty($vals)) {
            return NULL;
        }

        return implode('##', $vals);
    }

    /**
     * Check whether any boxes in the checkbox where checked.
     *
     * @param mixed $value The submitted values
     * @param mixed $name
     * @return bool
     */
    function notemptyfield($value, $name) {
        $found = false;
        foreach ($value as $checkboxitem) {
            if (strval($checkboxitem) !== '') {
                $found = true;
                break;
            }
        }
        return $found;
    }

    /**
     * Returns the presentable string value for a field content.
     *
     * The returned string should be plain text.
     *
     * @param stdClass $content
     * @return string
     */
    public static function get_content_value($content) {
        $arr = explode('##', $content->content);

        $strvalue = '';
        foreach ($arr as $a) {
            $strvalue .= $a . ' ';
        }

        return trim($strvalue, "\r\n ");
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of config parameters
     * @since Moodle 3.3
     */
    public function get_config_for_external() {
        // Return all the config parameters.
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = $this->field->{"param$i"};
        }
        return $configs;
    }
}
