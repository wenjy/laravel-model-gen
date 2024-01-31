<?php
/**
 * @date: 9:48 2023/4/10
 */

namespace WenGen;

abstract class Generator
{
    /**
     * @var array a list of available code templates. The array keys are the template names,
     * the array values are the corresponding template paths or path aliases.
     */
    public $templates = [];
    /**
     * @var string the name of the code template that the user has selected.
     * The value of this property is internally managed by this class.
     */
    public $template = 'default';

    public function __construct()
    {
        if (!isset($this->templates['default'])) {
            $this->templates['default'] = $this->defaultTemplate();
        }
    }

    /**
     * Returns the name of the code generator.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Generates the code based on the current user input and the specified code template files.
     */
    abstract public function generate();

    /**
     * Returns the message to be displayed when the newly generated code is saved successfully.
     * Child classes may override this method to customize the message.
     * @return string the message to be displayed when the newly generated code is saved successfully.
     */
    public function successMessage()
    {
        return 'The code has been generated successfully.';
    }

    /**
     * Returns the root path to the default code template files.
     * The default implementation will return the "templates" subdirectory of the
     * directory containing the generator class file.
     * @return string the root path to the default code template files.
     */
    public function defaultTemplate()
    {
        $class = new \ReflectionClass($this);

        return dirname($class->getFileName()) . DIRECTORY_SEPARATOR . 'default';
    }

    /**
     * Returns the detailed description of the generator.
     *
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * Saves the generated code into files.
     * @param CodeFile[] $files the code files to be saved
     * @param array $answers
     * @param string $results this parameter receives a value from this method indicating the log messages
     * generated while saving the code files.
     * @return bool whether files are successfully saved without any error.
     */
    public function save($files, $answers, &$results)
    {
        $lines = ['Generating code using template "' . $this->getTemplatePath() . '"...'];
        $hasError = false;
        foreach ($files as $file) {
            $relativePath = $file->getRelativePath();
            if (isset($answers[$file->id]) && !empty($answers[$file->id]) && $file->operation !== CodeFile::OP_SKIP) {
                $error = $file->save();
                if (is_string($error)) {
                    $hasError = true;
                    $lines[] = "generating $relativePath\n<span class=\"error\">$error</span>";
                } else {
                    $lines[] = $file->operation === CodeFile::OP_CREATE ? " generated $relativePath" : " overwrote $relativePath";
                }
            } else {
                $lines[] = "   skipped $relativePath";
            }
        }
        $lines[] = "done!\n";
        $results = implode("\n", $lines);

        return !$hasError;
    }

    /**
     * @return string the root path of the template files that are currently being used.
     * @throws \InvalidArgumentException if [[template]] is invalid
     */
    public function getTemplatePath()
    {
        if (isset($this->templates[$this->template])) {
            return $this->templates[$this->template];
        }

        throw new \InvalidArgumentException("Unknown template: {$this->template}");
    }
}
