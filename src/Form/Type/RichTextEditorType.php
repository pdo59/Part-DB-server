<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RichTextEditorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver); // TODO: Change the autogenerated stub

        $resolver->setDefault('mode', 'full');
        $resolver->setAllowedValues('mode', ['full', 'single_line']);

        $resolver->setDefault('output_format', 'markdown');
        $resolver->setAllowedValues('output_format', ['markdown']);
    }

    public function getBlockPrefix()
    {
        return 'rich_text_editor';
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], $this->optionsToAttrArray($options));

        parent::finishView($view, $form, $options); // TODO: Change the autogenerated stub
    }

    protected function optionsToAttrArray(array $options)
    {
        $tmp = [];

        $tmp['data-mode'] = $options['mode'];
        $tmp['data-output-format'] = $options['output_format'];

        //Add our data-controller element to the textarea
        $tmp['data-controller'] = 'elements--ckeditor';

        return $tmp;
    }

    public function getParent()
    {
        return TextareaType::class;
    }
}