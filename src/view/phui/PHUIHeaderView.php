<?php

final class PHUIHeaderView extends AphrontTagView {

  const PROPERTY_STATUS = 1;

  private $objectName;
  private $header;
  private $tags = array();
  private $image;
  private $imageURL = null;
  private $subheader;
  private $headerColor;
  private $noBackground;
  private $bleedHeader;
  private $properties = array();
  private $actionLinks = array();
  private $buttonBar = null;
  private $policyObject;
  private $epoch;

  public function setHeader($header) {
    $this->header = $header;
    return $this;
  }

  public function setObjectName($object_name) {
    $this->objectName = $object_name;
    return $this;
  }

  public function setNoBackground($nada) {
    $this->noBackground = $nada;
    return $this;
  }

  public function addTag(PHUITagView $tag) {
    $this->tags[] = $tag;
    return $this;
  }

  public function setImage($uri) {
    $this->image = $uri;
    return $this;
  }

  public function setImageURL($url) {
    $this->imageURL = $url;
    return $this;
  }

  public function setSubheader($subheader) {
    $this->subheader = $subheader;
    return $this;
  }

  public function setBleedHeader($bleed) {
    $this->bleedHeader = $bleed;
    return $this;
  }

  public function setHeaderColor($color) {
    $this->headerColor = $color;
    return $this;
  }

  public function setPolicyObject(PhabricatorPolicyInterface $object) {
    $this->policyObject = $object;
    return $this;
  }

  public function addProperty($property, $value) {
    $this->properties[$property] = $value;
    return $this;
  }

  public function addActionLink(PHUIButtonView $button) {
    $this->actionLinks[] = $button;
    return $this;
  }

  public function setButtonBar(PHUIButtonBarView $bb) {
    $this->buttonBar = $bb;
    return $this;
  }

  public function setStatus($icon, $color, $name) {
    $header_class = 'phui-header-status';

    if ($color) {
      $icon = $icon.' '.$color;
      $header_class = $header_class.'-'.$color;
    }

    $img = id(new PHUIIconView())
      ->setIconFont($icon);

    $tag = phutil_tag(
      'span',
      array(
        'class' => "{$header_class} plr",
      ),
      array(
        $img,
        $name,
      ));

    return $this->addProperty(self::PROPERTY_STATUS, $tag);
  }

  public function setEpoch($epoch) {
    $age = time() - $epoch;
    $age = floor($age / (60 * 60 * 24));
    if ($age < 1) {
      $when = pht('Today');
    } else if ($age == 1) {
      $when = pht('Yesterday');
    } else {
      $when = pht('%d Days Ago', $age);
    }

    $this->setStatus('fa-clock-o bluegrey', null, pht('Updated %s', $when));
    return $this;
  }

  protected function getTagName() {
    return 'div';
  }

  protected function getTagAttributes() {
    require_celerity_resource('phui-header-view-css');

    $classes = array();
    $classes[] = 'phui-header-shell';

    if ($this->noBackground) {
      $classes[] = 'phui-header-no-backgound';
    }

    if ($this->bleedHeader) {
      $classes[] = 'phui-bleed-header';
    }

    if ($this->headerColor) {
      $classes[] = 'sprite-gradient';
      $classes[] = 'gradient-'.$this->headerColor.'-header';
    }

    if ($this->properties || $this->policyObject || $this->subheader) {
      $classes[] = 'phui-header-tall';
    }

    if ($this->image) {
      $classes[] = 'phui-header-has-image';
    }

    return array(
      'class' => $classes,
    );
  }

  protected function getTagContent() {
    $image = null;
    if ($this->image) {
      $image = phutil_tag(
        ($this->imageURL ? 'a' : 'span'),
        array(
          'href' => $this->imageURL,
          'class' => 'phui-header-image',
          'style' => 'background-image: url('.$this->image.')',
        ),
        ' ');
    }

    $viewer = $this->getUser();

    $header = array();
    if ($viewer) {
      $header[] = id(new PHUISpacesNamespaceContextView())
        ->setUser($viewer)
        ->setObject($this->policyObject);
    }

    if ($this->objectName) {
      $header[] = array(
        phutil_tag(
          'a',
          array(
            'href' => '/'.$this->objectName,
          ),
          $this->objectName),
        ' ',
      );
    }

    if ($this->actionLinks) {
      $actions = array();
      foreach ($this->actionLinks as $button) {
        $button->setColor(PHUIButtonView::SIMPLE);
        $button->addClass(PHUI::MARGIN_SMALL_LEFT);
        $button->addClass('phui-header-action-link');
        $actions[] = $button;
      }
      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-action-links',
        ),
        $actions);
    }

    if ($this->buttonBar) {
      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-action-links',
        ),
        $this->buttonBar);
    }
    $header[] = $this->header;

    if ($this->tags) {
      $header[] = ' ';
      $header[] = phutil_tag(
        'span',
        array(
          'class' => 'phui-header-tags',
        ),
        array_interleave(' ', $this->tags));
    }

    if ($this->subheader) {
      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-subheader',
        ),
        $this->subheader);
    }

    if ($this->properties || $this->policyObject) {
      $property_list = array();
      foreach ($this->properties as $type => $property) {
        switch ($type) {
          case self::PROPERTY_STATUS:
            $property_list[] = $property;
          break;
          default:
            throw new Exception(pht('Incorrect Property Passed'));
          break;
        }
      }

      if ($this->policyObject) {
        $property_list[] = $this->renderPolicyProperty($this->policyObject);
      }

      $header[] = phutil_tag(
        'div',
        array(
          'class' => 'phui-header-subheader',
        ),
        $property_list);
    }

    return array(
      $image,
      phutil_tag(
        'h1',
        array(
          'class' => 'phui-header-view grouped',
        ),
        $header),
      );
  }

  private function renderPolicyProperty(PhabricatorPolicyInterface $object) {
    $viewer = $this->getUser();

    $policies = PhabricatorPolicyQuery::loadPolicies($viewer, $object);

    $view_capability = PhabricatorPolicyCapability::CAN_VIEW;
    $policy = idx($policies, $view_capability);
    if (!$policy) {
      return null;
    }

    // If an object is in a Space with a strictly stronger (more restrictive)
    // policy, we show the more restrictive policy. This better aligns the
    // UI hint with the actual behavior.

    // NOTE: We'll do this even if the viewer has access to only one space, and
    // show them information about the existence of spaces if they click
    // through.
    $use_space_policy = false;
    if ($object instanceof PhabricatorSpacesInterface) {
      $space_phid = PhabricatorSpacesNamespaceQuery::getObjectSpacePHID(
        $object);

      $spaces = PhabricatorSpacesNamespaceQuery::getViewerSpaces($viewer);
      $space = idx($spaces, $space_phid);
      if ($space) {
        $space_policies = PhabricatorPolicyQuery::loadPolicies(
          $viewer,
          $space);
        $space_policy = idx($space_policies, $view_capability);
        if ($space_policy) {
          if ($space_policy->isStrongerThan($policy)) {
            $policy = $space_policy;
            $use_space_policy = true;
          }
        }
      }
    }

    $container_classes = array();
    $container_classes[] = 'policy-header-callout';
    $phid = $object->getPHID();

    // If we're going to show the object policy, try to determine if the object
    // policy differs from the default policy. If it does, we'll call it out
    // as changed.
    if (!$use_space_policy) {
      $default_policy = PhabricatorPolicyQuery::getDefaultPolicyForObject(
        $viewer,
        $object,
        $view_capability);
      if ($default_policy) {
        if ($default_policy->getPHID() != $policy->getPHID()) {
          $container_classes[] = 'policy-adjusted';
          if ($default_policy->isStrongerThan($policy)) {
            // The policy has strictly been weakened. For example, the
            // default might be "All Users" and the current policy is "Public".
            $container_classes[] = 'policy-adjusted-weaker';
          } else if ($policy->isStrongerThan($default_policy)) {
            // The policy has strictly been strengthened, and is now more
            // restrictive than the default. For example, "All Users" has
            // been replaced with "No One".
            $container_classes[] = 'policy-adjusted-stronger';
          } else {
            // The policy has been adjusted but not strictly strengthened
            // or weakened. For example, "Members of X" has been replaced with
            // "Members of Y".
            $container_classes[] = 'policy-adjusted-different';
          }
        }
      }
    }

    $icon = id(new PHUIIconView())
      ->setIconFont($policy->getIcon().' bluegrey');

    $link = javelin_tag(
      'a',
      array(
        'class' => 'policy-link',
        'href' => '/policy/explain/'.$phid.'/'.$view_capability.'/',
        'sigil' => 'workflow',
      ),
      $policy->getShortName());

    return phutil_tag(
      'span',
      array(
        'class' => implode(' ', $container_classes),
      ),
      array($icon, $link));
  }

}
