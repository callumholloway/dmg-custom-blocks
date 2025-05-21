import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

registerBlockType('dmg/second-block', {
  edit() {
    const blockProps = useBlockProps({
      style: { paddingTop: '32px', paddingBottom: '32px', paddingLeft: '8px', paddingRight: '8px' }
    });
    return (
      <div {...blockProps}>
        <h2>Hello, this is the second block!</h2>
        <p>We are testing the functionality of the second block.</p>
      </div>
    );
  },

  save() {
    const blockProps = useBlockProps.save({
      style: { paddingTop: '32px', paddingBottom: '32px', paddingLeft: '8px', paddingRight: '8px' }
    });
    return (
      <div {...blockProps}>
        <h2>Hello, this is the second block!</h2>
        <p>We are testing the functionality of the second block.</p>
      </div>
    );
  }
});