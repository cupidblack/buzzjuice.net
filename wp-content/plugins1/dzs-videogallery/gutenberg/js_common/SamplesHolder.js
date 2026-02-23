import React from 'react';

export default class SamplesHolder extends React.Component {
  constructor(props)  {
    super(props);

    this.props = props;
    this.expanded = false;
    this.state = {expanded: false};
  }

  render(){
    return (
      <div className={this.state.expanded ? "gt-dzs-examples-con opened" : "gt-dzs-examples-con " }>
        <h6 style={{marginBottom: 0}} onClick={() => {
          this.setState({expanded: !this.state.expanded});
        }}>{ this.props.__( 'Examples' ) } <span className={"the-icon"}> &#x025BF;</span></h6>
        <div className={"sidenote"}>{ this.props.__( 'Import examples with one click' ) }</div>
        <div className={"dzs-player-examples-con"}>
          {this.props.arr_sample_data.map((el,index1) => {
            const imgPath = String(el.img).indexOf('https')===0 ? el.img : (String(el.img).indexOf('baseurl')>-1 ? String(el.img).replace('{{baseurl}}', this.props.main_settings.base_pluginPath) : String(el.img));
            return (
              <div key={index1} className={"dzs-player-example"} onClick={(e) => {  this.props.import_sample(e.currentTarget) }} data-import-type={el.type} data-the-name={el.name}>
                <img className={"the-img"} src={imgPath}/>
                <h6 className={"the-label"}>{el.label}</h6>
              </div>
            )
          })}
        </div>
      </div>
    )
  }
}