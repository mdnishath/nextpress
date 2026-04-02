<?php

declare(strict_types=1);

namespace NextPressBuilder\Modules\ComponentLibrary\Service;

use NextPressBuilder\Core\Repository\ComponentRepository;

/**
 * Manages the component registry — all 20 section types with content schemas.
 *
 * Each component defines what fields it has (content_schema), default content,
 * and default styling. Like Elementor's Widget definitions.
 */
class ComponentService
{
    public function __construct(
        private readonly ComponentRepository $repo,
    ) {}

    /**
     * Seed all 20 built-in components if none exist.
     */
    public function seedComponents(): void
    {
        if ($this->repo->count() > 0) {
            return;
        }

        foreach ($this->getBuiltInComponents() as $component) {
            $this->repo->create($component);
        }
    }

    /**
     * Get all 20 built-in component definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getBuiltInComponents(): array
    {
        return [
            // 1. Hero
            [
                'slug' => 'hero',
                'name' => 'Hero Section',
                'category' => 'hero',
                'description' => 'Full-width hero with heading, CTA buttons, background image, and optional form.',
                'is_user_created' => 0,
                'content_schema' => [
                    'fields' => [
                        ['key'=>'badge','type'=>'text','label'=>'Badge Text','default'=>''],
                        ['key'=>'heading','type'=>'text','label'=>'Heading','required'=>true,'default'=>'Your Business Headline'],
                        ['key'=>'subtitle','type'=>'text','label'=>'Subtitle','default'=>''],
                        ['key'=>'description','type'=>'textarea','label'=>'Description','default'=>''],
                        ['key'=>'image','type'=>'image','label'=>'Background Image'],
                        ['key'=>'badges','type'=>'repeater','label'=>'Trust Badges','fields'=>[
                            ['key'=>'text','type'=>'text','label'=>'Badge Text'],
                        ]],
                        ['key'=>'cta_primary_text','type'=>'text','label'=>'Primary CTA','default'=>'Get Started'],
                        ['key'=>'cta_primary_link','type'=>'url','label'=>'Primary CTA Link','default'=>'/contact'],
                        ['key'=>'cta_primary_button','type'=>'button_preset','label'=>'Primary Button Style'],
                        ['key'=>'cta_secondary_text','type'=>'text','label'=>'Secondary CTA','default'=>''],
                        ['key'=>'cta_secondary_link','type'=>'url','label'=>'Secondary CTA Link'],
                        ['key'=>'form_id','type'=>'form_select','label'=>'Embedded Form'],
                        ['key'=>'show_form','type'=>'boolean','label'=>'Show Form','default'=>false],
                    ],
                ],
                'default_content' => ['heading'=>'Your Trusted Local Business','subtitle'=>'Professional Service You Can Count On','description'=>'We provide top-quality service with years of experience.','cta_primary_text'=>'Get Free Quote','cta_primary_link'=>'/contact'],
                'default_style' => ['bgOverlayColor'=>'#000000','bgOverlayOpacity'=>50],
            ],
            // 2. Services Grid
            [
                'slug' => 'services_grid',
                'name' => 'Services / Features Grid',
                'category' => 'features',
                'description' => 'Grid of service cards with icons, images, and descriptions.',
                'is_user_created' => 0,
                'content_schema' => [
                    'fields' => [
                        ['key'=>'heading','type'=>'text','label'=>'Section Heading','default'=>'Our Services'],
                        ['key'=>'subtitle','type'=>'text','label'=>'Subtitle','default'=>'What we offer'],
                        ['key'=>'description','type'=>'textarea','label'=>'Description'],
                        ['key'=>'columns','type'=>'select','label'=>'Columns','options'=>['2','3','4'],'default'=>'3'],
                        ['key'=>'items','type'=>'repeater','label'=>'Services','fields'=>[
                            ['key'=>'icon','type'=>'icon','label'=>'Icon'],
                            ['key'=>'image','type'=>'image','label'=>'Image'],
                            ['key'=>'title','type'=>'text','label'=>'Title'],
                            ['key'=>'description','type'=>'textarea','label'=>'Description'],
                            ['key'=>'link','type'=>'url','label'=>'Link'],
                        ]],
                    ],
                ],
                'default_content' => ['heading'=>'Our Services','columns'=>'3','items'=>[
                    ['title'=>'Service One','description'=>'Description of your first service.'],
                    ['title'=>'Service Two','description'=>'Description of your second service.'],
                    ['title'=>'Service Three','description'=>'Description of your third service.'],
                ]],
                'default_style' => [],
            ],
            // 3. Testimonials
            [
                'slug' => 'testimonials',
                'name' => 'Testimonials',
                'category' => 'social_proof',
                'description' => 'Customer reviews with star ratings, avatars, and company info.',
                'is_user_created' => 0,
                'content_schema' => [
                    'fields' => [
                        ['key'=>'heading','type'=>'text','label'=>'Section Heading','default'=>'What Our Clients Say'],
                        ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                        ['key'=>'items','type'=>'repeater','label'=>'Testimonials','fields'=>[
                            ['key'=>'name','type'=>'text','label'=>'Name'],
                            ['key'=>'role','type'=>'text','label'=>'Role/Company'],
                            ['key'=>'avatar','type'=>'image','label'=>'Photo'],
                            ['key'=>'rating','type'=>'number','label'=>'Rating (1-5)','min'=>1,'max'=>5,'default'=>5],
                            ['key'=>'quote','type'=>'textarea','label'=>'Quote'],
                        ]],
                    ],
                ],
                'default_content' => ['heading'=>'What Our Clients Say','items'=>[
                    ['name'=>'John Smith','role'=>'Homeowner','rating'=>5,'quote'=>'Excellent service! Highly recommended.'],
                    ['name'=>'Sarah Johnson','role'=>'Business Owner','rating'=>5,'quote'=>'Professional and reliable. Would use again.'],
                    ['name'=>'Mike Davis','role'=>'Property Manager','rating'=>5,'quote'=>'Fast response and quality work.'],
                ]],
                'default_style' => [],
            ],
            // 4. CTA Banner
            [
                'slug' => 'cta_banner',
                'name' => 'CTA Banner',
                'category' => 'cta',
                'description' => 'Call-to-action banner with background, heading, and buttons.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','required'=>true,'default'=>'Ready to Get Started?'],
                    ['key'=>'description','type'=>'textarea','label'=>'Description'],
                    ['key'=>'image','type'=>'image','label'=>'Background Image'],
                    ['key'=>'cta_text','type'=>'text','label'=>'Button Text','default'=>'Contact Us'],
                    ['key'=>'cta_link','type'=>'url','label'=>'Button Link','default'=>'/contact'],
                    ['key'=>'cta_button','type'=>'button_preset','label'=>'Button Style'],
                    ['key'=>'cta_secondary_text','type'=>'text','label'=>'Secondary Button'],
                    ['key'=>'cta_secondary_link','type'=>'url','label'=>'Secondary Link'],
                ]],
                'default_content' => ['heading'=>'Ready to Get Started?','description'=>'Contact us today for a free consultation.','cta_text'=>'Get Free Quote','cta_link'=>'/contact'],
                'default_style' => [],
            ],
            // 5. Pricing
            [
                'slug' => 'pricing',
                'name' => 'Pricing Table',
                'category' => 'pricing',
                'description' => 'Pricing plans with features list, monthly/annual toggle.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Our Pricing'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'show_toggle','type'=>'boolean','label'=>'Show Monthly/Annual Toggle','default'=>false],
                    ['key'=>'plans','type'=>'repeater','label'=>'Plans','fields'=>[
                        ['key'=>'name','type'=>'text','label'=>'Plan Name'],
                        ['key'=>'price','type'=>'text','label'=>'Price'],
                        ['key'=>'period','type'=>'text','label'=>'Period','default'=>'/month'],
                        ['key'=>'description','type'=>'text','label'=>'Description'],
                        ['key'=>'features','type'=>'repeater','label'=>'Features','fields'=>[['key'=>'text','type'=>'text','label'=>'Feature'],['key'=>'included','type'=>'boolean','label'=>'Included','default'=>true]]],
                        ['key'=>'cta_text','type'=>'text','label'=>'Button Text','default'=>'Choose Plan'],
                        ['key'=>'cta_link','type'=>'url','label'=>'Button Link'],
                        ['key'=>'highlighted','type'=>'boolean','label'=>'Highlighted','default'=>false],
                    ]],
                ]],
                'default_content' => ['heading'=>'Simple, Transparent Pricing'],
                'default_style' => [],
            ],
            // 6. Team
            [
                'slug' => 'team',
                'name' => 'Team Members',
                'category' => 'people',
                'description' => 'Team member cards with photos, roles, and social links.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Meet Our Team'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'members','type'=>'repeater','label'=>'Members','fields'=>[
                        ['key'=>'name','type'=>'text','label'=>'Name'],
                        ['key'=>'role','type'=>'text','label'=>'Role'],
                        ['key'=>'photo','type'=>'image','label'=>'Photo'],
                        ['key'=>'bio','type'=>'textarea','label'=>'Bio'],
                        ['key'=>'linkedin','type'=>'url','label'=>'LinkedIn'],
                        ['key'=>'twitter','type'=>'url','label'=>'Twitter'],
                        ['key'=>'email','type'=>'text','label'=>'Email'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Meet Our Team'],
                'default_style' => [],
            ],
            // 7. FAQ
            [
                'slug' => 'faq',
                'name' => 'FAQ Accordion',
                'category' => 'faq',
                'description' => 'Frequently asked questions with expandable answers.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Frequently Asked Questions'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'items','type'=>'repeater','label'=>'Questions','fields'=>[
                        ['key'=>'question','type'=>'text','label'=>'Question'],
                        ['key'=>'answer','type'=>'richtext','label'=>'Answer'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Frequently Asked Questions','items'=>[
                    ['question'=>'What services do you offer?','answer'=>'We offer a wide range of professional services.'],
                    ['question'=>'How much does it cost?','answer'=>'Contact us for a free estimate.'],
                    ['question'=>'Do you offer free estimates?','answer'=>'Yes! All estimates are free with no obligation.'],
                ]],
                'default_style' => [],
            ],
            // 8. Gallery
            [
                'slug' => 'gallery',
                'name' => 'Image Gallery',
                'category' => 'media',
                'description' => 'Photo gallery with lightbox, filters, and masonry layout.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Our Work'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'columns','type'=>'select','label'=>'Columns','options'=>['2','3','4'],'default'=>'3'],
                    ['key'=>'images','type'=>'repeater','label'=>'Images','fields'=>[
                        ['key'=>'image','type'=>'image','label'=>'Image'],
                        ['key'=>'caption','type'=>'text','label'=>'Caption'],
                        ['key'=>'category','type'=>'text','label'=>'Category'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Our Work','columns'=>'3'],
                'default_style' => [],
            ],
            // 9. Stats Counter
            [
                'slug' => 'stats_counter',
                'name' => 'Stats / Counters',
                'category' => 'data',
                'description' => 'Animated number counters for key statistics.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading'],
                    ['key'=>'items','type'=>'repeater','label'=>'Stats','fields'=>[
                        ['key'=>'number','type'=>'text','label'=>'Number','default'=>'100'],
                        ['key'=>'suffix','type'=>'text','label'=>'Suffix','default'=>'+'],
                        ['key'=>'label','type'=>'text','label'=>'Label'],
                        ['key'=>'icon','type'=>'icon','label'=>'Icon'],
                    ]],
                ]],
                'default_content' => ['items'=>[
                    ['number'=>'500','suffix'=>'+','label'=>'Happy Clients'],
                    ['number'=>'15','suffix'=>'+','label'=>'Years Experience'],
                    ['number'=>'1000','suffix'=>'+','label'=>'Projects Done'],
                    ['number'=>'24','suffix'=>'/7','label'=>'Support'],
                ]],
                'default_style' => [],
            ],
            // 10. Contact Form
            [
                'slug' => 'contact_form',
                'name' => 'Contact / Map',
                'category' => 'contact',
                'description' => 'Contact section with form, map embed, and business info.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Contact Us'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'form_id','type'=>'form_select','label'=>'Contact Form'],
                    ['key'=>'show_map','type'=>'boolean','label'=>'Show Map','default'=>true],
                    ['key'=>'show_info','type'=>'boolean','label'=>'Show Business Info','default'=>true],
                    ['key'=>'map_embed','type'=>'textarea','label'=>'Google Maps Embed URL'],
                ]],
                'default_content' => ['heading'=>'Get In Touch','show_map'=>true,'show_info'=>true],
                'default_style' => [],
            ],
            // 11. Timeline
            [
                'slug' => 'timeline',
                'name' => 'Timeline',
                'category' => 'history',
                'description' => 'Vertical timeline for processes, history, or milestones.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Our Process'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'items','type'=>'repeater','label'=>'Steps','fields'=>[
                        ['key'=>'title','type'=>'text','label'=>'Title'],
                        ['key'=>'description','type'=>'textarea','label'=>'Description'],
                        ['key'=>'icon','type'=>'icon','label'=>'Icon'],
                        ['key'=>'date','type'=>'text','label'=>'Date/Label'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Our Process','items'=>[
                    ['title'=>'Step 1: Consultation','description'=>'We discuss your needs and provide a free estimate.'],
                    ['title'=>'Step 2: Planning','description'=>'We create a detailed plan for your project.'],
                    ['title'=>'Step 3: Execution','description'=>'Our team completes the work to the highest standards.'],
                ]],
                'default_style' => [],
            ],
            // 12. Portfolio
            [
                'slug' => 'portfolio',
                'name' => 'Portfolio / Projects',
                'category' => 'work',
                'description' => 'Filterable portfolio grid with project details.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Our Projects'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'show_filters','type'=>'boolean','label'=>'Show Category Filters','default'=>true],
                    ['key'=>'items','type'=>'repeater','label'=>'Projects','fields'=>[
                        ['key'=>'title','type'=>'text','label'=>'Title'],
                        ['key'=>'image','type'=>'image','label'=>'Image'],
                        ['key'=>'category','type'=>'text','label'=>'Category'],
                        ['key'=>'description','type'=>'textarea','label'=>'Description'],
                        ['key'=>'link','type'=>'url','label'=>'Link'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Our Projects','show_filters'=>true],
                'default_style' => [],
            ],
            // 13. Blog/News
            [
                'slug' => 'blog_news',
                'name' => 'Blog / News',
                'category' => 'content',
                'description' => 'Blog post cards with excerpts, dates, and pagination.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Latest News'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'source','type'=>'select','label'=>'Source','options'=>['manual','wordpress_posts'],'default'=>'manual'],
                    ['key'=>'post_count','type'=>'number','label'=>'Number of Posts','default'=>3],
                    ['key'=>'items','type'=>'repeater','label'=>'Manual Posts','fields'=>[
                        ['key'=>'title','type'=>'text','label'=>'Title'],
                        ['key'=>'image','type'=>'image','label'=>'Image'],
                        ['key'=>'excerpt','type'=>'textarea','label'=>'Excerpt'],
                        ['key'=>'date','type'=>'text','label'=>'Date'],
                        ['key'=>'link','type'=>'url','label'=>'Link'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Latest News','source'=>'manual','post_count'=>3],
                'default_style' => [],
            ],
            // 14. Newsletter
            [
                'slug' => 'newsletter',
                'name' => 'Newsletter Signup',
                'category' => 'cta',
                'description' => 'Email signup form for newsletter subscriptions.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Stay Updated'],
                    ['key'=>'description','type'=>'textarea','label'=>'Description','default'=>'Subscribe to our newsletter for the latest updates.'],
                    ['key'=>'placeholder','type'=>'text','label'=>'Input Placeholder','default'=>'Enter your email'],
                    ['key'=>'button_text','type'=>'text','label'=>'Button Text','default'=>'Subscribe'],
                    ['key'=>'form_id','type'=>'form_select','label'=>'Newsletter Form'],
                ]],
                'default_content' => ['heading'=>'Stay Updated','button_text'=>'Subscribe'],
                'default_style' => [],
            ],
            // 15. About
            [
                'slug' => 'about',
                'name' => 'About / Values',
                'category' => 'about',
                'description' => 'About section with mission, vision, values, and team story.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'About Us'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'content','type'=>'richtext','label'=>'Content'],
                    ['key'=>'image','type'=>'image','label'=>'Image'],
                    ['key'=>'values','type'=>'repeater','label'=>'Values','fields'=>[
                        ['key'=>'icon','type'=>'icon','label'=>'Icon'],
                        ['key'=>'title','type'=>'text','label'=>'Title'],
                        ['key'=>'description','type'=>'textarea','label'=>'Description'],
                    ]],
                ]],
                'default_content' => ['heading'=>'About Us','content'=>'We are a trusted local business with years of experience.'],
                'default_style' => [],
            ],
            // 16. Zone/Service Area
            [
                'slug' => 'zone_intervention',
                'name' => 'Zone / Service Area',
                'category' => 'location',
                'description' => 'Service area map with list of locations served.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'Areas We Serve'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'map_embed','type'=>'textarea','label'=>'Map Embed URL'],
                    ['key'=>'zones','type'=>'repeater','label'=>'Service Areas','fields'=>[
                        ['key'=>'name','type'=>'text','label'=>'Area Name'],
                        ['key'=>'description','type'=>'text','label'=>'Description'],
                    ]],
                ]],
                'default_content' => ['heading'=>'Areas We Serve'],
                'default_style' => [],
            ],
            // 17. Before/After
            [
                'slug' => 'before_after',
                'name' => 'Before / After Slider',
                'category' => 'comparison',
                'description' => 'Image comparison slider showing before and after.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading','default'=>'See the Difference'],
                    ['key'=>'subtitle','type'=>'text','label'=>'Subtitle'],
                    ['key'=>'items','type'=>'repeater','label'=>'Comparisons','fields'=>[
                        ['key'=>'before_image','type'=>'image','label'=>'Before Image'],
                        ['key'=>'after_image','type'=>'image','label'=>'After Image'],
                        ['key'=>'caption','type'=>'text','label'=>'Caption'],
                    ]],
                ]],
                'default_content' => ['heading'=>'See the Difference'],
                'default_style' => [],
            ],
            // 18. Rich Text
            [
                'slug' => 'rich_text',
                'name' => 'Rich Text Block',
                'category' => 'content',
                'description' => 'Free-form rich text content block with full formatting.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'content','type'=>'richtext','label'=>'Content','required'=>true],
                    ['key'=>'max_width','type'=>'select','label'=>'Max Width','options'=>['sm','md','lg','xl','full'],'default'=>'lg'],
                ]],
                'default_content' => ['content'=>'<p>Add your content here...</p>','max_width'=>'lg'],
                'default_style' => [],
            ],
            // 19. Divider/Spacer
            [
                'slug' => 'divider',
                'name' => 'Divider / Spacer',
                'category' => 'layout',
                'description' => 'Visual divider or spacer between sections.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'type','type'=>'select','label'=>'Type','options'=>['line','space','dots','wave','angle'],'default'=>'line'],
                    ['key'=>'height','type'=>'number','label'=>'Height (px)','default'=>40],
                    ['key'=>'color','type'=>'color','label'=>'Color'],
                    ['key'=>'width','type'=>'select','label'=>'Width','options'=>['25%','50%','75%','100%'],'default'=>'100%'],
                ]],
                'default_content' => ['type'=>'line','height'=>40,'width'=>'100%'],
                'default_style' => [],
            ],
            // 20. Embed
            [
                'slug' => 'embed',
                'name' => 'Embed (Video/Map/Code)',
                'category' => 'media',
                'description' => 'Embed YouTube, Google Maps, or custom HTML/code.',
                'is_user_created' => 0,
                'content_schema' => ['fields'=>[
                    ['key'=>'heading','type'=>'text','label'=>'Heading'],
                    ['key'=>'embed_type','type'=>'select','label'=>'Type','options'=>['youtube','vimeo','google_maps','custom_html'],'default'=>'youtube'],
                    ['key'=>'url','type'=>'url','label'=>'URL'],
                    ['key'=>'html','type'=>'textarea','label'=>'Custom HTML'],
                    ['key'=>'aspect_ratio','type'=>'select','label'=>'Aspect Ratio','options'=>['16:9','4:3','1:1','21:9'],'default'=>'16:9'],
                ]],
                'default_content' => ['embed_type'=>'youtube','aspect_ratio'=>'16:9'],
                'default_style' => [],
            ],
        ];
    }
}
