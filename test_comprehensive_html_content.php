<?php

/**
 * Test script for comprehensive HTML content
 * 
 * This script creates content with all HTML elements to test the styling
 */

// Base URL for the API
$baseUrl = 'http://localhost:8000/api/content';

// Comprehensive HTML content example
$comprehensiveHtmlContent = [
    'title' => 'Comprehensive HTML Content Test',
    'slug' => 'comprehensive-html-test',
    'description' => '<h1>Main Title - H1</h1>
<p>This is a paragraph with <strong>bold text</strong>, <em>italic text</em>, and <u>underlined text</u>. It also contains <code>inline code</code> and a <a href="https://example.com" target="_blank">link to example.com</a>.</p>

<h2>Section 1 - H2</h2>
<p>This section demonstrates various text formatting options including <mark>highlighted text</mark>, <del>deleted text</del>, and <ins>inserted text</ins>.</p>

<h3>Subsection 1.1 - H3</h3>
<p>Here we have some <small>small text</small> and <sup>superscript</sup> and <sub>subscript</sub> text.</p>

<h4>Subsection 1.1.1 - H4</h4>
<p>This is a paragraph with <abbr title="HyperText Markup Language">HTML</abbr> abbreviation.</p>

<h5>Subsection 1.1.1.1 - H5</h5>
<p>This paragraph contains <cite>cited text</cite> and <q>quoted text</q>.</p>

<h6>Subsection 1.1.1.1.1 - H6</h6>
<p>This is the smallest heading level.</p>

<h2>Lists Section - H2</h2>

<h3>Unordered List - H3</h3>
<ul>
    <li>First unordered item</li>
    <li>Second unordered item with <strong>bold text</strong></li>
    <li>Third unordered item with <em>italic text</em></li>
    <li>Nested list:
        <ul>
            <li>Nested item 1</li>
            <li>Nested item 2</li>
        </ul>
    </li>
</ul>

<h3>Ordered List - H3</h3>
<ol>
    <li>First ordered item</li>
    <li>Second ordered item</li>
    <li>Third ordered item with nested list:
        <ol>
            <li>Nested ordered item 1</li>
            <li>Nested ordered item 2</li>
        </ol>
    </li>
</ol>

<h3>Definition List - H3</h3>
<dl>
    <dt>HTML</dt>
    <dd>HyperText Markup Language - the standard markup language for creating web pages.</dd>
    <dt>CSS</dt>
    <dd>Cascading Style Sheets - used for styling HTML documents.</dd>
    <dt>JavaScript</dt>
    <dd>A programming language that enables interactive web pages.</dd>
</dl>

<h2>Table Section - H2</h2>
<table>
    <thead>
        <tr>
            <th>Element</th>
            <th>Purpose</th>
            <th>Example</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>&lt;h1&gt;</code></td>
            <td>Main heading</td>
            <td>Page title</td>
        </tr>
        <tr>
            <td><code>&lt;p&gt;</code></td>
            <td>Paragraph</td>
            <td>Body text</td>
        </tr>
        <tr>
            <td><code>&lt;strong&gt;</code></td>
            <td>Bold text</td>
            <td><strong>Important</strong></td>
        </tr>
        <tr>
            <td><code>&lt;em&gt;</code></td>
            <td>Italic text</td>
            <td><em>Emphasis</em></td>
        </tr>
        <tr>
            <td><code>&lt;a&gt;</code></td>
            <td>Link</td>
            <td><a href="#">Click here</a></td>
        </tr>
    </tbody>
</table>

<h2>Code Section - H2</h2>
<p>Here is some inline code: <code>const example = "Hello World";</code></p>

<p>And here is a code block:</p>
<pre><code>function greetUser(name) {
    console.log(`Hello, ${name}!`);
    return `Welcome, ${name}`;
}

const user = "John";
const greeting = greetUser(user);</code></pre>

<h2>Blockquote Section - H2</h2>
<blockquote>
    <p>This is a blockquote with some important information that needs to stand out from the regular text. It can contain multiple paragraphs and other HTML elements.</p>
    <p>This is the second paragraph in the blockquote.</p>
</blockquote>

<h2>Address Section - H2</h2>
<address>
    <strong>Benefitowo sp. z o.o.</strong><br>
    ul. Example Street 123<br>
    00-000 Warsaw, Poland<br>
    Email: <a href="mailto:kontakt@simplybooking.pl">kontakt@simplybooking.pl</a><br>
    Phone: +48 123 456 789
</address>

<h2>Horizontal Rule - H2</h2>
<p>This is content before the horizontal rule.</p>
<hr>
<p>This is content after the horizontal rule.</p>

<h2>Image Section - H2</h2>
<p>Here is an example of how images would be displayed (using a placeholder):</p>
<p><img src="https://via.placeholder.com/400x200/4f46e5/ffffff?text=Sample+Image" alt="Sample image placeholder" style="max-width: 100%; height: auto;"></p>

<h2>Mixed Content Section - H2</h2>
<p>This section demonstrates how different elements work together:</p>
<ul>
    <li>Lists can contain <strong>bold text</strong> and <em>italic text</em></li>
    <li>They can also contain <a href="#">links</a> and <code>code</code></li>
    <li>Even <mark>highlighted text</mark> works well in lists</li>
</ul>

<p>Tables can also contain various formatting:</p>
<table>
    <thead>
        <tr>
            <th>Feature</th>
            <th>Status</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>HTML Support</strong></td>
            <td>✅ <em>Active</em></td>
            <td>Full <code>HTML5</code> support</td>
        </tr>
        <tr>
            <td><strong>CSS Styling</strong></td>
            <td>✅ <em>Active</em></td>
            <td>Custom <mark>content-page</mark> styles</td>
        </tr>
        <tr>
            <td><strong>Security</strong></td>
            <td>✅ <em>Active</em></td>
            <td>Basic <del>XSS</del> protection</td>
        </tr>
    </tbody>
</table>',
    'isActive' => true
];

echo "Comprehensive HTML Content Test Script\n";
echo "=====================================\n\n";

// Test: Create comprehensive HTML content
echo "1. Creating comprehensive HTML content...\n";
$createResponse = makeRequest('POST', $baseUrl, $comprehensiveHtmlContent);
echo "Response: " . $createResponse['response'] . "\n";
echo "Status: " . $createResponse['status'] . "\n\n";

if ($createResponse['status'] === 201) {
    $createdContent = json_decode($createResponse['response'], true);
    $contentId = $createdContent['id'];
    $slug = $createdContent['slug'];
    
    echo "2. Content created successfully!\n";
    echo "   ID: $contentId\n";
    echo "   Slug: $slug\n";
    echo "   URL: http://localhost:3000/content/$slug\n\n";
    
    echo "3. This content demonstrates:\n";
    echo "   - All heading levels (H1-H6)\n";
    echo "   - Text formatting (bold, italic, underline, etc.)\n";
    echo "   - Lists (ordered, unordered, definition)\n";
    echo "   - Tables with proper styling\n";
    echo "   - Code blocks and inline code\n";
    echo "   - Blockquotes\n";
    echo "   - Links and other elements\n";
    echo "   - Mixed content combinations\n\n";
}

echo "Test completed!\n";
echo "\nNext steps:\n";
echo "1. Visit http://localhost:3000/content/comprehensive-html-test to see all styled elements\n";
echo "2. All HTML elements should be properly styled according to the content-page CSS\n";
echo "3. The styling should match the design system with proper typography and spacing\n";

/**
 * Make HTTP request
 */
function makeRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'response' => $response,
        'status' => $httpCode
    ];
}
