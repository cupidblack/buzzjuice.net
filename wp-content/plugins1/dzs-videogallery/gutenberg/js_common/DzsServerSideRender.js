import React from 'react';

export default class DzsServerSideRender extends React.Component {
  constructor(props)  {
    super(props);

    this.props = props;
    this.state = { hasError: false };

    console.log(this.props);
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI.
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    console.log(error, errorInfo, this.props);
  }
  render(){
    if (this.state.hasError) {

      return <h1>Something went wrong.</h1>;
    }


    return (
      <ServerSideRender
        block={this.props.keyBlock}
        attributes={this.props.attributes}
      />
    )
  }
}