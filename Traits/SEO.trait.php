<?php

namespace Cpeople\Traits;

trait SEO
{
    public function getSEOKeywords()
    {
        return strip_tags($this->getPropText('SEO_KEYWORDS'));
    }

    public function getSEODescription()
    {
        return strip_tags($this->getPropText('SEO_DESCRIPTION'));
    }

    public function getSEOImage()
    {
        return $this->getImageThumb('SEO_IMAGE', array('type' => 'square', 'size' => 200));
    }

    public function hasSEOImage()
    {
        return $this->hasImage('SEO_IMAGE');
    }
}

/*
<meta name="keywords" content="" />
<meta name="description" content="" />
<link rel="image_src" href="" />

<!-- Facebook Like -->
<meta property="og:title" content="" />
<meta property="og:url" content="" />
<meta property="og:description" content="" />
<meta property="og:site_name" content="" />
<meta property="og:image" content="" />

<!-- Google -->
<meta itemprop="name" content="" />
<meta itemprop="url" content="" />
<meta itemprop="description" content="" />
<meta itemprop="image" content="" />*/