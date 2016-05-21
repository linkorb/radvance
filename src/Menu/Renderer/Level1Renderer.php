<?php

namespace Radvance\Menu\Renderer;

use Knp\Menu\Renderer\RendererInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\ItemInterface;

class Level1Renderer implements RendererInterface
{
    protected $matcher;
    protected $urlGenerator;
    
    public function __construct(MatcherInterface $matcher, $urlGenerator)
    {
        $this->matcher = $matcher;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function render(ItemInterface $item, array $options = array())
    {
        $o = '<ul class="level1menu nav nav-sidebar">';
        foreach ($item->getChildren() as $item) {
            $o .= '<li';
            $current = false;
            if ($this->matcher->isCurrent($item)) {
                $current = true;
            }
            if ($this->matcher->isAncestor($item, 10)) {
                $current = true;
            }
            if ($current) {
                $o .= ' class="current"';
            }
            $o .= '>';
            $o .= '<a href="' . $this->urlGenerator->generate($item->getUri()) . '">';
            $o .= $item->getLabel();
            $o .= '</a>';
            if ($current) {
                foreach ($item->getChildren() as $subItem) {
                    $o .= '<ul>';
                    
                    
                    $current = false;
                    if ($this->matcher->isCurrent($subItem)) {
                        $current = true;
                    }
                    if ($this->matcher->isAncestor($subItem, 10)) {
                        $current = true;
                    }

                    $o .= '<li';
                    if ($current) {
                        $o .= ' class="current"';
                    }
                    $o .= '>';
                    $o .= '<a href="' . $this->urlGenerator->generate($subItem->getUri())  . '">';
                    $o .= $subItem->getLabel();
                    $o .= '</a>';
                    $o .= '</li>';

                    $o .= '</ul>';
                }
            }
            $o .= '</li>';
        }
        $o .= '</ul>';
        return $o;
    }
}
