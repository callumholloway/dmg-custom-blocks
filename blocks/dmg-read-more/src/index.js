import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, TextControl, Button, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import metadata from '../block.json';
import {
  InspectorControls,
  useBlockProps,
} from '@wordpress/block-editor';

// Register the block
registerBlockType(metadata.name, {
  edit({ attributes, setAttributes }) {
    const { 
      postId, 
      postTitle, 
      postLink,
    } = attributes;
    
    // Use standard block props which will automatically handle styles from the block editor
    const blockProps = useBlockProps({
      style: {
        padding: '8px',  // Add intrinsic padding - we could also use spacing from theme.json here if we wanted to control it
      }
    });

	// State variables for managing the post selection and pagination
	const [query, setQuery] = useState('');
    const [posts, setPosts] = useState([]);
    const [page, setPage] = useState(1);
    const [isLoading, setIsLoading] = useState(false);
    const [totalPages, setTotalPages] = useState(1);

	// Fetch posts from the WordPress REST API
    const fetchPosts = async (queryString = '', pageNumber = 1) => {
      setIsLoading(true);
      let params = {
        per_page: 10, //brief didn't specify how many we want... let's say 10. could derive from site posts per page if we really wanted
		status: 'publish', // the brief does specify we only want published posts!
        page: pageNumber,
        _embed: true,
      };

	  
      if (queryString) {
        if (/^\d+$/.test(queryString)) {
          params.include = [parseInt(queryString)];
        } else {
          params.search = queryString;
        }
      }

      try {
        const response = await apiFetch({
          path: addQueryArgs('/wp/v2/posts', params),
          parse: false,
        });

        const results = await response.json();
        const totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '1', 10);

        setPosts(results);
        setTotalPages(totalPages);
      } catch (err) {
        console.error('Error fetching posts', err);
        setPosts([]);
      } finally {
        setIsLoading(false);
      }
    };

    useEffect(() => {
      fetchPosts(query, page);
    }, [query, page]);

    const selectPost = (post) => {
      setAttributes({
        postId: post.id,
        postTitle: post.title.rendered,
        postLink: post.link,
      });
    };

    return (
      <>
        <InspectorControls>
          <PanelBody title="Select a Post" initialOpen={true}>
            <TextControl
              label="Search Posts or Enter Post ID"
              value={query}
              onChange={(value) => {
                setQuery(value);
                setPage(1);
              }}
            />
            {isLoading ? (
              <Spinner />
            ) : (
              <div>
                {posts.length > 0 ? (
                  posts.map((post) => (
                    <Button
                      isPressed={post.id === postId}
                      key={post.id}
                      onClick={() => selectPost(post)}
                      style={{ display: 'block', marginBottom: '8px', textAlign: 'left' }}
                    >
                      {post.title?.rendered || 'Untitled Post'}
                    </Button>
                  ))
                ) : (
                  <p>No posts found.</p>
                )}
                {totalPages > 1 && (
                  <div style={{ marginTop: '1em' }}>
                    <Button  disabled={page === 1} onClick={() => setPage(page - 1)}>
                      Previous
                    </Button>
                    <Button
                      disabled={page === totalPages}
                      onClick={() => setPage(page + 1)}
                      style={{ marginLeft: '10px' }}
                    >
                      Next
                    </Button>
                  </div>
                )}
              </div>
            )}
          </PanelBody>
        </InspectorControls>
		
        <div {...blockProps}>
          {postId ? (
            <p className="dmg-read-more">
              Read More: <a 
                href={postLink} 
                className="wp-element-link" // This class allows link color to be applied
              >
                {postTitle}
              </a>
            </p>
          ) : ( // If no post is selected, show a message to guide the CMS user
            <p>Select a post from the Inspector panel.</p>
          )}
        </div>
      </>
    );
  },
  

// Save function is used to save the block's content in the post content
  save({ attributes }) {
    const { 
      postId, 
      postTitle, 
      postLink,
    } = attributes;

    if (!postId || !postTitle || !postLink) return null;

    // Apply all styling properties from the block editor
    const blockProps = useBlockProps.save({
      className: "dmg-read-more",
      style: {
        padding: '8px',  // Add intrinsic padding
      }
    });

    return (
      <div {...blockProps}>
        <p>
          Read More: <a 
            href={postLink} 
            className="wp-element-link"
          >
            {postTitle}
          </a>
        </p>
      </div>
    );
  },
});