/**
 * Please keep in mind that since Moodle 2.0 all of the CSS for the whole of
 * combined into one file, stripped of white spaces, then delivered.
 *
 * Please visit the CSS coding style page:
 * https://docs.moodle.org/dev/CSS_coding_style
 */
 
sass --watch style/scss/moodle.scss:style/moodle.css

//http://foselearning.knowtechture.com/moodle/mod/data/templates.php?d=2&mode=csstemplate

FROM 01004cad36d82e37895e866ac50c70dc08617b8b29bed28a4273cfd9f74ae183 # ID base de la imagen

# Instalar locales
RUN apt-get update && 
    apt-get install -y locales && 
    locale-gen pt_PT.UTF-8

# Configurar locales
ENV LANG es_ES.UTF-8
ENV LANGUAGE es_ESes
ENV LC_ALL es_ES.UTF-8

ENV LANG fr_FR.UTF-8
ENV LANGUAGE fr_FRfr
ENV LC_ALL fr_FR.UTF-8

ENV LANG pt_PT.UTF-8
ENV LANGUAGE pt_PTpt
ENV LC_ALL pt_PT.UTF-8

