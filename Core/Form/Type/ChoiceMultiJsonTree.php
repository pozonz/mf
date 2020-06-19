<?php
namespace MillenniumFalcon\Core\Form\Type;

use \BlueM\Tree\Node;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceMultiJsonTree extends AbstractType
{

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'choice_multi_json_tree';
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var Node $root */
        $root = $options['choices'];

        $view->vars['choices'] = array();
        foreach ($root as $itm) {
            $view->vars['choices'] = array_merge($view->vars['choices'], $this->getChoices($itm, 1));
        }
    }

    /**
     * @param Node $node
     * @param $level
     * @return array
     */
    private function getChoices(Node $node, $level) {
        $result = [];
        $result[] = [
            'level' => $level,
            'value' => $node->getId(),
            'label' => $node->getTitle(),
        ];
        foreach ($node->getChildren() as $itm) {
            $result = array_merge($result, $this->getChoices($itm, $level + 1));
        }
        return $result;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'compound' => false,
            'choices' => array(),
            'placeholder' => "Choose options...",
        ));
    }
}

